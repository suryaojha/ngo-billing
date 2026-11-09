<?php
require_once 'config.php';

/* ------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Only files inside /uploads are ever used as images/videos.
function isValidFilePath($path){
    if(empty($path)) return false;
    $realPath=realpath($path);
    $uploadsDir=realpath('uploads');
    return $realPath && $uploadsDir && strpos($realPath,$uploadsDir)===0;
}

// Admin-chosen colours are written into CSS, so only accept safe values.
function safeColor($c,$default){
    $c=trim((string)$c);
    if(preg_match('/^#[0-9a-fA-F]{3,8}$/',$c)||preg_match('/^[a-zA-Z]{3,20}$/',$c)) return $c;
    return $default;
}

// Indian digit grouping: 1234567 -> 12,34,567
function inr($n): string {
    $n=(float)$n; $neg=$n<0; $s=number_format(abs($n),0,'.','');
    if(strlen($s)>3){ $last=substr($s,-3); $rest=preg_replace('/\B(?=(\d{2})+(?!\d))/',',',substr($s,0,-3)); $s=$rest.','.$last; }
    return ($neg?'-':'').$s;
}

// Works even if the PHP "mbstring" extension is missing on the host.
if(!function_exists('mb_substr')){ function mb_substr($s,$a,$l=null){ return $l===null?substr($s,$a):substr($s,$a,$l); } }
if(!function_exists('mb_strlen')){ function mb_strlen($s){ return strlen($s); } }

function donationCols(PDO $pdo){
    static $cols=null;
    if($cols===null){ $cols=[]; foreach($pdo->query("SHOW COLUMNS FROM Donations") as $r) $cols[]=$r['Field']; }
    return $cols;
}

// Insert a donation using only the columns this database really has
// (some installs lack PaymentMethod / TransactionID).
function insertDonation(PDO $pdo,array $d){
    $cols=donationCols($pdo);
    $d['DonationType']=$d['PaymentMethod']??'';
    if(!in_array('TransactionID',$cols,true) && !empty($d['TransactionID'])){
        $d['Message']=trim(($d['Message']??'').' [Ref: '.$d['TransactionID'].']');
    }
    $row=[]; foreach($d as $k=>$v){ if(in_array($k,$cols,true)) $row[$k]=$v; }
    $sql="INSERT INTO Donations(".implode(',',array_keys($row)).")VALUES(".implode(',',array_fill(0,count($row),'?')).")";
    $pdo->prepare($sql)->execute(array_values($row));
}

// Ask Razorpay whether a payment id is real, and for how much.
function rzpFetchPayment($id,$key,$secret){
    if(!function_exists('curl_init')) return null;
    $ch=curl_init('https://api.razorpay.com/v1/payments/'.rawurlencode($id));
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_USERPWD=>$key.':'.$secret,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>true]);
    $res=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if($res===false) return null;
    $j=json_decode($res,true);
    if($code===200 && is_array($j)) return $j;
    // 400/404 = Razorpay says this payment id does not exist -> invalid.
    // Anything else (401/403 bad keys, 5xx, network) = we simply could not verify -> caller keeps it Pending.
    return ($code===400||$code===404) ? ['status'=>'invalid'] : null;
}

// Razorpay credentials
$razorpayKeyId='rzp_live_RygDIfainScYaz';
$razorpayKeySecret='pWH09E0gWKbuU51ZIWopuw2o';

/* ------------------------------------------------------------------
 * Donation handlers (post -> redirect -> get, so a refresh never double-posts)
 * ------------------------------------------------------------------ */
$formError='';
if($_SERVER['REQUEST_METHOD']==='POST' && (isset($_POST['razorpay_payment_id'])||isset($_POST['qr_donate']))){
    $name=trim((string)($_POST['name']??''));
    $email=trim((string)($_POST['email']??''));
    $phone=preg_replace('/[^0-9+ ]/','',(string)($_POST['phone']??''));
    $amount=(float)($_POST['amount']??0);
    $campaignId=!empty($_POST['campaign_id'])?(int)$_POST['campaign_id']:null;
    $message=trim((string)($_POST['message']??''));
    $payId=trim((string)($_POST['razorpay_payment_id']??''));

    if(mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen(preg_replace('/\D/','',$phone))<10){
        $formError='Please enter your name, a valid email and a 10-digit phone number.';
    }elseif($amount<1||$amount>1000000){
        $formError='Please enter a donation amount between ₹1 and ₹10,00,000.';
    }else{
        try{
            if($payId!==''){
                // ---- Razorpay: never trust the browser; confirm the payment with Razorpay ----
                if(!preg_match('/^pay_[A-Za-z0-9]+$/',$payId)) throw new Exception('bad payment id');
                // same payment id already recorded (page refresh / double submit)?
                if(in_array('TransactionID',donationCols($pdo),true)){
                    $dup=$pdo->prepare("SELECT COUNT(*) FROM Donations WHERE TransactionID=? OR Message LIKE ?");
                    $dup->execute([$payId,'%'.$payId.'%']);
                }else{
                    $dup=$pdo->prepare("SELECT COUNT(*) FROM Donations WHERE Message LIKE ?");
                    $dup->execute(['%'.$payId.'%']);
                }
                if((int)$dup->fetchColumn()>0){ header('Location: index.php?donated=1#home'); exit; }

                $pay=rzpFetchPayment($payId,$razorpayKeyId,$razorpayKeySecret);
                $status='Pending'; $paid=$amount;
                if($pay){
                    if(in_array($pay['status']??'',['captured','authorized'],true)){
                        $status='Approved'; $paid=((float)($pay['amount']??0))/100;   // amount Razorpay really received
                    }else{
                        throw new Exception('payment not successful');
                    }
                }   // if Razorpay could not be reached we keep it Pending for the admin to verify
                insertDonation($pdo,['DonorName'=>$name,'Email'=>$email,'Phone'=>$phone,'Amount'=>$paid,'CampaignID'=>$campaignId,
                    'Message'=>$message,'PaymentMethod'=>'Razorpay','TransactionID'=>$payId,'Status'=>$status]);
                if($status==='Approved' && $campaignId){
                    $pdo->prepare("UPDATE Campaigns SET CurrentAmount=CurrentAmount+? WHERE CampaignID=?")->execute([$paid,$campaignId]);
                }
                header('Location: index.php?donated='.($status==='Approved'?'1':'2').'#home'); exit;
            }else{
                // ---- UPI QR: waits for the admin to verify it in the dashboard ----
                $utr=trim((string)($_POST['utr']??''));
                insertDonation($pdo,['DonorName'=>$name,'Email'=>$email,'Phone'=>$phone,'Amount'=>$amount,'CampaignID'=>$campaignId,
                    'Message'=>$message,'PaymentMethod'=>'QR Code','TransactionID'=>$utr,'Status'=>'Pending']);
                header('Location: index.php?donated=2#home'); exit;
            }
        }catch(Exception $e){
            error_log('Donation error: '.$e->getMessage());
            $formError='We could not record your donation. If money was deducted, please contact us with your payment reference.';
        }
    }
}
$donatedFlag=(int)($_GET['donated']??0);

/* ------------------------------------------------------------------
 * Site content (all editable from admin.php)
 * ------------------------------------------------------------------ */
function setting(PDO $pdo,$key,$default=''){
    $v=$pdo->prepare("SELECT SettingValue FROM AdminSettings WHERE SettingKey=?");
    $v->execute([$key]); $r=$v->fetchColumn();
    return ($r===false||$r===null||$r==='')?$default:$r;
}

try{
    $contact=$pdo->query("SELECT * FROM ContactInfo ORDER BY ContactID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $themeSettings=$pdo->query("SELECT * FROM ThemeSettings ORDER BY SettingID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $campaigns=$pdo->query("SELECT * FROM Campaigns WHERE IsActive=1 AND IsFeatured=1 ORDER BY CreatedDate DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $testimonials=$pdo->query("SELECT * FROM Testimonials WHERE IsActive=1 ORDER BY DisplayOrder,CreatedDate DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $gallery=$pdo->query("SELECT * FROM Gallery WHERE IsActive=1 ORDER BY UploadDate DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    $partners=$pdo->query("SELECT * FROM Partners WHERE IsActive=1 ORDER BY DisplayOrder,CreatedDate DESC")->fetchAll(PDO::FETCH_ASSOC);
    $events=$pdo->query("SELECT * FROM Events WHERE IsActive=1 AND ShowPopup=1 AND EventDate>=CURDATE() ORDER BY EventDate ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $logoPath=setting($pdo,'LogoPath');
    $heroVideo=setting($pdo,'HeroVideoPath');
    $bgPath=setting($pdo,'BackgroundPath');
    $siteHeading=setting($pdo,'SiteHeading','Relief Wave Foundation');
    $primaryColor=safeColor(setting($pdo,'PrimaryColor',$themeSettings['BackgroundColor']??'#11998e'),'#11998e');
    $secondaryColor=safeColor(setting($pdo,'SecondaryColor','#38ef7d'),'#38ef7d');
    $gradientType=setting($pdo,'GradientType','linear');
    $gradientDirection=preg_match('/^\d{1,3}deg$/',setting($pdo,'GradientDirection','135deg'))?setting($pdo,'GradientDirection','135deg'):'135deg';
    $fontColor=safeColor(setting($pdo,'FontColor','#0f172a'),'#0f172a');
    $qrCodeModal=setting($pdo,'QRCodePath');
    $upiId=setting($pdo,'UpiId');
    $aboutSubtitle=setting($pdo,'AboutSubtitle','Transforming lives through compassion, dedication, and sustainable impact across India');
    $missionText=setting($pdo,'MissionText','To empower underprivileged communities through education, healthcare, and sustainable development programs that create lasting positive change.');
    $visionText=setting($pdo,'VisionText','A world where everyone has access to opportunities, dignity, and the chance to build a better future for themselves and their families.');
    $livesImpacted=(int)setting($pdo,'LivesImpacted',1000);
    $projectsCompleted=(int)setting($pdo,'ProjectsCompleted',50);
    $communitiesServed=(int)setting($pdo,'CommunitiesServed',25);
    $yearsOfService=(int)setting($pdo,'YearsOfService',5);
    $heroFontColor=safeColor(setting($pdo,'HeroFontColor','#ffffff'),'#ffffff');
    $bgOpacity=max(0,min(1,(float)setting($pdo,'ImageOpacity',0.35)));
    $sectionOrder=[];
    foreach(['about'=>1,'stats'=>2,'programs'=>3,'gallery'=>4,'partners'=>5] as $k=>$def){ $sectionOrder[$k]=(int)setting($pdo,'SectionOrder_'.$k,$def); }
    asort($sectionOrder);
    $stats=$pdo->query("SELECT
        (SELECT COUNT(*) FROM Donations WHERE Status='Approved' AND IsActive=1) as donations,
        (SELECT IFNULL(SUM(Amount),0) FROM Donations WHERE Status='Approved' AND IsActive=1) as amount,
        (SELECT COUNT(*) FROM Partners WHERE IsActive=1) as partners,
        (SELECT COUNT(*) FROM Campaigns WHERE IsActive=1) as campaigns")->fetch(PDO::FETCH_ASSOC);
}catch(Exception $e){
    error_log('Site load error: '.$e->getMessage());
    $contact=null;$themeSettings=null;$campaigns=[];$testimonials=[];$gallery=[];$partners=[];$events=null;$logoPath='';$heroVideo='';$bgPath='';
    $siteHeading='Relief Wave Foundation';$primaryColor='#11998e';$secondaryColor='#38ef7d';$gradientType='linear';$gradientDirection='135deg';$fontColor='#0f172a';
    $qrCodeModal='';$upiId='';$aboutSubtitle='Transforming lives through compassion, dedication, and sustainable impact across India';
    $missionText='To empower underprivileged communities through education, healthcare, and sustainable development programs.';
    $visionText='A world where everyone has access to opportunities, dignity, and the chance to build a better future.';
    $livesImpacted=1000;$projectsCompleted=50;$communitiesServed=25;$yearsOfService=5;$heroFontColor='#ffffff';$bgOpacity=0.35;
    $sectionOrder=['about'=>1,'stats'=>2,'programs'=>3,'gallery'=>4,'partners'=>5];$stats=['donations'=>0,'amount'=>0,'partners'=>0,'campaigns'=>0];
}
$contact=$contact?:['Phone'=>'','Email'=>'','Address'=>'','WhatsAppNumber'=>'','InstagramURL'=>'','FacebookURL'=>'','TwitterURL'=>'','LinkedInURL'=>''];
$themeSettings=$themeSettings?:['HeroTitle'=>'Together for Hope, Together for Change','HeroSubtitle'=>'Empowering communities across India','ShowAboutSection'=>1,'ShowStatsSection'=>1,'ShowProgramsSection'=>1,'ShowGallerySection'=>1,'ShowPartnersSection'=>1];

$gradient=$gradientType==='solid'?$primaryColor:($gradientType==='radial'?"radial-gradient(circle,$primaryColor 0%,$secondaryColor 100%)":"linear-gradient($gradientDirection,$primaryColor 0%,$secondaryColor 100%)");
$hasLogo=$logoPath && isValidFilePath($logoPath);
$hasVideo=$heroVideo && isValidFilePath($heroVideo);
$hasBg=$bgPath && isValidFilePath($bgPath);
$hasQr=$qrCodeModal && isValidFilePath($qrCodeModal);
$waNumber=preg_replace('/\D/','',(string)($contact['WhatsAppNumber']??''));
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'https':'http';
$siteUrl=$scheme.'://'.($_SERVER['HTTP_HOST']??'');
$logoUrl=$hasLogo?$siteUrl.'/'.ltrim($logoPath,'/'):'';
$urlOk=function($u){ $u=trim((string)$u); return preg_match('#^https?://#i',$u)?$u:''; };
$social=array_filter([
    'Facebook'=>$urlOk($contact['FacebookURL']??''),'Instagram'=>$urlOk($contact['InstagramURL']??''),
    'X / Twitter'=>$urlOk($contact['TwitterURL']??''),'LinkedIn'=>$urlOk($contact['LinkedInURL']??''),
]);
$socialIcon=['Facebook'=>'f','Instagram'=>'◎','X / Twitter'=>'𝕏','LinkedIn'=>'in'];

// Which sections are visible -> navigation
$show=[
  'about'=>!empty($themeSettings['ShowAboutSection']),
  'stats'=>!empty($themeSettings['ShowStatsSection']),
  'programs'=>!empty($themeSettings['ShowProgramsSection']) && $campaigns,
  'gallery'=>!empty($themeSettings['ShowGallerySection']) && $gallery,
  'partners'=>!empty($themeSettings['ShowPartnersSection']) && $partners,
];
$nav=[['home','Home']];
if($show['about']) $nav[]=['about','About'];
if($show['programs']) $nav[]=['campaigns','Campaigns'];
if($show['gallery']) $nav[]=['gallery','Gallery'];
if($testimonials) $nav[]=['stories','Stories'];
$nav[]=['contact','Contact'];

$heroTitle=$themeSettings['HeroTitle']??'';
$heroSub=$themeSettings['HeroSubtitle']??'';
$metaDesc=mb_substr(trim(strip_tags($heroSub?:$aboutSubtitle)),0,160);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($siteHeading) ?> — <?= h($heroTitle?:'Together for Hope') ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<meta name="theme-color" content="<?= h($primaryColor) ?>">
<meta property="og:title" content="<?= h($siteHeading) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<meta property="og:type" content="website">
<?php if($logoUrl): ?><meta property="og:image" content="<?= h($logoUrl) ?>"><link rel="icon" href="<?= h($logoPath) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{--p:<?= h($primaryColor) ?>;--s:<?= h($secondaryColor) ?>;--grad:<?= $gradient ?>;--ink:#0f172a;--muted:#64748b;--bg:#f8fafc;--line:#e2e8f0;--brand:<?= h($fontColor) ?>;--hero:<?= h($heroFontColor) ?>;--r:22px;--shadow:0 18px 40px -18px rgba(15,23,42,.28)}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:76px}
body{font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--ink);background:#fff;line-height:1.65;overflow-x:hidden;-webkit-font-smoothing:antialiased}
h1,h2,h3,h4{font-family:'Poppins','Inter',sans-serif;line-height:1.2}
img{max-width:100%;display:block}
a{color:inherit}
button{font-family:inherit}
.container{max-width:1180px;margin:0 auto;padding:0 20px}
:focus-visible{outline:3px solid var(--p);outline-offset:2px}

/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 28px;border-radius:999px;border:2px solid transparent;font-weight:600;font-size:1rem;cursor:pointer;text-decoration:none;transition:transform .2s,box-shadow .2s,background .2s;white-space:nowrap}
.btn:hover{transform:translateY(-2px)}
.btn-primary{background:var(--grad);color:#fff;box-shadow:0 12px 26px -10px rgba(0,0,0,.45)}
.btn-ghost{background:rgba(255,255,255,.14);color:#fff;border-color:rgba(255,255,255,.55);backdrop-filter:blur(6px)}
.btn-ghost:hover{background:rgba(255,255,255,.26)}
.btn-outline{background:#fff;color:var(--p);border-color:var(--p)}
.btn-outline:hover{background:var(--p);color:#fff}
.btn-sm{padding:10px 20px;font-size:.92rem}
.btn-block{width:100%}

/* nav */
.nav{position:fixed;inset:0 0 auto 0;z-index:1000;transition:background .3s,box-shadow .3s,padding .3s;padding:14px 0}
.nav-in{display:flex;align-items:center;justify-content:space-between;gap:16px}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:1.15rem;min-width:0}
.brand img{height:42px;width:42px;object-fit:cover;border-radius:12px;background:#fff}
.brand span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:46vw}
.menu{display:flex;align-items:center;gap:6px;list-style:none}
.menu a{display:block;padding:9px 15px;border-radius:999px;text-decoration:none;color:#fff;font-weight:500;font-size:.95rem;transition:background .2s}
.menu a:hover{background:rgba(255,255,255,.18)}
.nav.scrolled{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);box-shadow:0 6px 24px -12px rgba(15,23,42,.35);padding:9px 0}
.nav.scrolled .brand{color:var(--brand)}
.nav.scrolled .menu a{color:var(--ink)}
.nav.scrolled .menu a:hover{background:#f1f5f9}
.burger{display:none;width:44px;height:44px;border-radius:12px;border:1px solid rgba(255,255,255,.5);background:rgba(255,255,255,.14);color:#fff;font-size:1.4rem;cursor:pointer}
.nav.scrolled .burger{border-color:var(--line);background:#fff;color:var(--ink)}

/* hero */
.hero{position:relative;min-height:100svh;display:flex;align-items:center;color:var(--hero);background:var(--grad);overflow:hidden;padding:110px 0 120px}
.hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;z-index:0}
.hero-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0}
.hero::before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(2,6,23,<?= max(.15,$bgOpacity-.1) ?>) 0%,rgba(2,6,23,<?= min(.85,$bgOpacity+.25) ?>) 100%);z-index:1}
.hero-in{position:relative;z-index:2;max-width:820px}
.eyebrow{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.35);backdrop-filter:blur(6px);padding:7px 16px;border-radius:999px;font-size:.85rem;font-weight:600;margin-bottom:22px}
.hero h1{font-size:clamp(2.1rem,5.6vw,4rem);font-weight:800;margin-bottom:20px;text-shadow:0 4px 24px rgba(0,0,0,.35)}
.hero p{font-size:clamp(1rem,2.2vw,1.2rem);max-width:680px;opacity:.95;margin-bottom:34px;text-shadow:0 2px 12px rgba(0,0,0,.3)}
.hero-cta{display:flex;gap:14px;flex-wrap:wrap}
.hero-strip{position:absolute;left:0;right:0;bottom:0;z-index:2;background:rgba(2,6,23,.45);backdrop-filter:blur(10px);border-top:1px solid rgba(255,255,255,.18)}
.hero-strip .container{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding-top:16px;padding-bottom:16px;text-align:center}
.hero-strip b{display:block;font-family:'Poppins',sans-serif;font-size:clamp(1.15rem,3vw,1.7rem);color:#fff}
.hero-strip span{font-size:.8rem;opacity:.85;text-transform:uppercase;letter-spacing:.06em}

/* sections */
.section{padding:90px 0}
.section.alt{background:var(--bg)}
.sec-head{text-align:center;max-width:720px;margin:0 auto 54px}
.sec-head .tag{display:inline-block;color:var(--p);font-weight:700;font-size:.82rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:10px}
.sec-head h2{font-size:clamp(1.8rem,4vw,2.7rem);font-weight:800;margin-bottom:14px}
.sec-head p{color:var(--muted);font-size:1.08rem}
.grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:26px}
.card{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:34px;box-shadow:var(--shadow);transition:transform .3s}
.card:hover{transform:translateY(-6px)}
.ico{width:58px;height:58px;border-radius:18px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:18px;color:#fff}
.card h3{font-size:1.4rem;margin-bottom:10px}
.card p{color:var(--muted)}
.counters{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;margin:46px 0 10px}
.counter{text-align:center;padding:30px 14px;border-radius:var(--r);background:var(--bg);border:1px solid var(--line)}
.counter b{display:block;font-family:'Poppins',sans-serif;font-size:clamp(1.9rem,4vw,2.8rem);color:var(--p);font-weight:800}
.counter span{color:var(--muted);font-weight:500}
.band{background:var(--grad);color:#fff;padding:70px 0;position:relative}
.band::before{content:'';position:absolute;inset:0;background:rgba(2,6,23,.35)}
.band .container{position:relative}
.band-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;text-align:center}
.band-grid b{display:block;font-family:'Poppins',sans-serif;font-size:clamp(1.8rem,4.5vw,3rem);font-weight:800}
.band-grid span{opacity:.9;font-weight:500}

/* campaigns */
.camp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:28px}
.camp{background:#fff;border:1px solid var(--line);border-radius:var(--r);overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column;transition:transform .3s}
.camp:hover{transform:translateY(-6px)}
.camp-img{height:210px;object-fit:cover;width:100%;background:var(--grad)}
.camp-ph{height:210px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:3.4rem}
.camp-body{padding:26px;display:flex;flex-direction:column;flex:1}
.camp h3{font-size:1.28rem;margin-bottom:10px}
.clamp{color:var(--muted);display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;white-space:pre-line;font-size:.97rem}
.clamp.open{-webkit-line-clamp:unset;display:block}
.more{background:none;border:none;color:var(--p);font-weight:600;cursor:pointer;padding:6px 0;text-align:left}
.bar{height:10px;background:#e8edf3;border-radius:99px;overflow:hidden;margin:18px 0 10px}
.bar i{display:block;height:100%;background:var(--grad);border-radius:99px;width:0;transition:width 1.2s ease}
.camp-meta{display:flex;justify-content:space-between;font-size:.9rem;color:var(--muted);gap:8px;flex-wrap:wrap}
.camp-meta b{color:var(--ink)}
.camp .btn{margin-top:20px}

/* gallery */
.gal{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.gal button{border:none;padding:0;background:#e2e8f0;border-radius:18px;overflow:hidden;cursor:zoom-in;aspect-ratio:1/1;position:relative}
.gal button:first-child{grid-column:span 2;grid-row:span 2}
.gal img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.gal button:hover img{transform:scale(1.08)}
.gal .cap{position:absolute;left:0;right:0;bottom:0;padding:26px 14px 12px;color:#fff;font-weight:600;font-size:.92rem;text-align:left;background:linear-gradient(transparent,rgba(2,6,23,.75));opacity:0;transition:opacity .3s}
.gal button:hover .cap,.gal button:focus-visible .cap{opacity:1}

/* partners */
.marq{overflow:hidden;mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent)}
.marq-track{display:flex;gap:18px;width:max-content;animation:marq 38s linear infinite}
.marq:hover .marq-track{animation-play-state:paused}
.partner{width:210px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px 14px;text-align:center;box-shadow:0 10px 24px -16px rgba(15,23,42,.35);flex-shrink:0}
.partner .lg{height:70px;display:flex;align-items:center;justify-content:center;margin-bottom:10px}
.partner .lg img{max-height:70px;max-width:130px;object-fit:contain}
.partner .ini{width:64px;height:64px;border-radius:18px;background:var(--grad);color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.partner b{display:block;font-size:.95rem}.partner small{color:var(--muted)}
@keyframes marq{to{transform:translateX(-50%)}}

/* stories */
.stories{display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:24px}
.story{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:30px;box-shadow:var(--shadow);position:relative}
.story::before{content:'“';position:absolute;top:6px;right:22px;font-size:5rem;font-family:Georgia,serif;color:var(--p);opacity:.18;line-height:1}
.story p{color:#334155;margin-bottom:22px;font-size:1.02rem}
.who{display:flex;align-items:center;gap:14px}
.who img,.who .av{width:50px;height:50px;border-radius:50%;object-fit:cover;flex-shrink:0}
.who .av{background:var(--grad);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}
.who b{display:block}.who small{color:var(--muted)}

/* footer */
.footer{background:#0b1220;color:#cbd5e1;padding:70px 0 26px}
.f-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1.2fr;gap:36px;margin-bottom:40px}
.footer h4{color:#fff;margin-bottom:16px;font-size:1.05rem}
.footer a{text-decoration:none;color:#cbd5e1}.footer a:hover{color:#fff}
.footer li{list-style:none;margin-bottom:9px}
.f-brand{display:flex;gap:12px;align-items:center;margin-bottom:14px;color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:1.2rem}
.f-brand img{height:46px;width:46px;border-radius:12px;object-fit:cover;background:#fff}
.f-line{display:flex;gap:10px;margin-bottom:12px;align-items:flex-start}
.socials{display:flex;gap:10px;margin-top:6px;flex-wrap:wrap}
.socials a{width:42px;height:42px;border-radius:12px;background:#1e293b;display:flex;align-items:center;justify-content:center;font-weight:700;transition:background .2s}
.socials a:hover{background:var(--p)}
.f-bottom{border-top:1px solid #1e293b;padding-top:22px;text-align:center;font-size:.9rem;color:#94a3b8}

/* floating actions */
.fabs{position:fixed;right:16px;bottom:20px;z-index:900;display:flex;flex-direction:column;gap:12px}
.fab{width:56px;height:56px;border-radius:50%;border:none;display:flex;align-items:center;justify-content:center;color:#fff;cursor:pointer;box-shadow:0 12px 26px -8px rgba(0,0,0,.5);transition:transform .2s;text-decoration:none}
.fab:hover{transform:scale(1.1)}
.fab.wa{background:#25d366}.fab.ig{background:linear-gradient(45deg,#f09433,#dc2743,#bc1888)}
.fab svg{width:28px;height:28px;fill:#fff}
.dbar{display:none}

/* modal / sheet */
.overlay{position:fixed;inset:0;background:rgba(2,6,23,.7);backdrop-filter:blur(4px);z-index:2000;display:none;align-items:center;justify-content:center;padding:18px;overflow-y:auto}
.overlay.open{display:flex}
.sheet{background:#fff;border-radius:26px;width:100%;max-width:560px;max-height:94vh;overflow-y:auto;padding:30px;position:relative;animation:pop .28s ease}
.x{position:absolute;top:14px;right:14px;width:40px;height:40px;border-radius:50%;border:none;background:#f1f5f9;font-size:1.5rem;cursor:pointer;line-height:1}
.x:hover{background:#e2e8f0}
.sheet h2{font-size:1.5rem;margin-bottom:4px;padding-right:44px}
.for{color:var(--muted);margin-bottom:20px;font-size:.95rem}.for b{color:var(--p)}
.lbl{display:block;font-weight:600;font-size:.88rem;margin:16px 0 8px}
.chips{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.chip{padding:12px 4px;border-radius:14px;border:2px solid var(--line);background:#fff;font-weight:700;cursor:pointer;transition:.15s;font-size:.98rem}
.chip:hover{border-color:var(--p)}
.chip.on{background:var(--p);border-color:var(--p);color:#fff}
.fld{width:100%;padding:14px 16px;border:2px solid var(--line);border-radius:14px;font:inherit;font-size:1rem;background:#fff;transition:border-color .2s}
.fld:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 4px rgba(0,0,0,.05)}
.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.tabs{display:grid;grid-template-columns:1fr 1fr;gap:8px;background:#f1f5f9;padding:5px;border-radius:16px;margin:20px 0 16px}
.tab{padding:12px 6px;border:none;border-radius:12px;background:transparent;font-weight:600;cursor:pointer;color:var(--muted)}
.tab.on{background:#fff;color:var(--ink);box-shadow:0 4px 12px -6px rgba(0,0,0,.3)}
.pane{display:none}.pane.on{display:block}
.qr{text-align:center;padding:6px 0 4px}
.qr img{width:200px;height:200px;object-fit:contain;margin:0 auto 12px;border-radius:16px;border:1px solid var(--line);padding:8px;background:#fff}
.upi{display:flex;align-items:center;gap:8px;justify-content:center;background:#f1f5f9;border-radius:12px;padding:10px 14px;font-weight:600;margin:10px 0;flex-wrap:wrap}
.upi button{border:none;background:var(--p);color:#fff;border-radius:8px;padding:6px 12px;cursor:pointer;font-weight:600}
.err{background:#fee2e2;color:#991b1b;border-radius:12px;padding:12px 14px;margin-bottom:14px;font-size:.93rem;display:none}
.err.show{display:block}
.secure{text-align:center;color:var(--muted);font-size:.82rem;margin-top:12px}
.thanks{text-align:center;padding:18px 6px}
.thanks .big{font-size:3.4rem}
.lb{background:rgba(2,6,23,.92);padding:0}
.lb-in{position:relative;max-width:1000px;width:100%;padding:14px;text-align:center}
.lb img{max-height:76vh;margin:0 auto;border-radius:16px;object-fit:contain}
.lb h3{color:#fff;margin-top:14px}.lb p{color:#cbd5e1;font-size:.95rem}
.lb .x{background:#fff;top:20px;right:20px}
.lb .nv{position:absolute;top:42%;width:46px;height:46px;border-radius:50%;border:none;background:rgba(255,255,255,.92);font-size:1.4rem;cursor:pointer}
.lb .nv.l{left:18px}.lb .nv.r{right:18px}
.event{position:fixed;left:16px;bottom:20px;z-index:950;max-width:340px;background:#fff;border:1px solid var(--line);border-radius:20px;padding:20px 20px 18px;box-shadow:0 24px 50px -16px rgba(15,23,42,.45);display:none;animation:pop .4s ease}
.event.open{display:block}
.event .x{width:32px;height:32px;font-size:1.2rem;top:10px;right:10px}
.event small{color:var(--p);font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:.72rem}
.event h4{margin:4px 0 8px;font-size:1.1rem;padding-right:30px}.event p{color:var(--muted);font-size:.9rem;margin-bottom:4px}
.toast{position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:3000;background:#0f172a;color:#fff;padding:14px 22px;border-radius:14px;box-shadow:0 20px 40px -12px rgba(0,0,0,.5);max-width:92vw;display:none}
.toast.open{display:block;animation:pop .3s ease}
@keyframes pop{from{opacity:0;transform:translateY(16px) scale(.97)}to{opacity:1;transform:none}}
.js .rv{opacity:0;transform:translateY(34px);transition:opacity .7s,transform .7s}
.js .rv.in{opacity:1;transform:none}

@media(max-width:900px){
 .grid-2{grid-template-columns:1fr}
 .counters,.band-grid{grid-template-columns:repeat(2,1fr)}
 .f-grid{grid-template-columns:1fr 1fr}
 .gal{grid-template-columns:repeat(2,1fr)}
 .gal button:first-child{grid-column:span 2;grid-row:span 1;aspect-ratio:16/10}
}
@media(max-width:760px){
 .burger{display:block}
 .menu{position:fixed;top:68px;left:12px;right:12px;flex-direction:column;align-items:stretch;background:#fff;border-radius:20px;padding:10px;box-shadow:0 24px 50px -12px rgba(15,23,42,.5);display:none}
 .menu.open{display:flex}
 .menu a,.nav.scrolled .menu a{color:var(--ink);padding:14px 16px;font-size:1.02rem}
 .menu a:hover{background:#f1f5f9}
 .section{padding:64px 0}
 .sec-head{margin-bottom:36px}
 .hero{padding:96px 0 150px;align-items:flex-end}
 .hero-cta .btn{flex:1;min-width:140px}
 .hero-strip .container{gap:6px}
 .card{padding:26px}
 .f-grid{grid-template-columns:1fr}
 .two{grid-template-columns:1fr}
 .chips{grid-template-columns:repeat(2,1fr)}
 .overlay{align-items:flex-end;padding:0}
 .sheet{border-radius:26px 26px 0 0;max-height:96vh;padding:24px 20px 28px;animation:sheet .3s ease}
 .fabs{bottom:86px}
 .dbar{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:950;background:#fff;border-top:1px solid var(--line);padding:10px 14px calc(10px + env(safe-area-inset-bottom));gap:10px;box-shadow:0 -10px 30px -12px rgba(15,23,42,.35);transform:translateY(110%);transition:transform .3s}
 .dbar.show{transform:none}
 .dbar .btn{flex:1}
 .event{left:12px;right:12px;max-width:none;bottom:86px}
 .lb .nv{top:auto;bottom:18px}
 .footer{padding-bottom:90px}
}
@keyframes sheet{from{transform:translateY(100%)}to{transform:none}}
@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}html{scroll-behavior:auto}.js .rv{opacity:1;transform:none}}
</style>
</head>
<body>
<script>document.documentElement.classList.add('js')</script>

<nav class="nav" id="nav" aria-label="Main">
 <div class="container nav-in">
  <a class="brand" href="#home">
   <?php if($hasLogo): ?><img src="<?= h($logoPath) ?>" alt=""><?php else: ?><span aria-hidden="true">🌊</span><?php endif; ?>
   <span><?= h($siteHeading) ?></span>
  </a>
  <ul class="menu" id="menu">
   <?php foreach($nav as [$id,$label]): ?><li><a href="#<?= $id ?>"><?= h($label) ?></a></li><?php endforeach; ?>
   <li><a href="#" data-donate class="btn btn-primary btn-sm" style="color:#fff;margin-left:6px">💝 Donate</a></li>
  </ul>
  <button class="burger" id="burger" aria-label="Open menu" aria-expanded="false">☰</button>
 </div>
</nav>

<header class="hero" id="home">
 <?php if($hasBg): ?><div class="hero-bg" style="background-image:url('<?= h($bgPath) ?>')"></div><?php endif; ?>
 <?php if($hasVideo): ?><video class="hero-video" autoplay muted loop playsinline><source src="<?= h($heroVideo) ?>" type="video/mp4"></video><?php endif; ?>
 <div class="container">
  <div class="hero-in">
   <span class="eyebrow">🤝 <?= h($siteHeading) ?></span>
   <h1><?= h($heroTitle) ?></h1>
   <p><?= nl2br(h($heroSub)) ?></p>
   <div class="hero-cta">
    <button class="btn btn-primary" data-donate>💝 Donate now</button>
    <?php if($show['programs']): ?><a class="btn btn-ghost" href="#campaigns">Our campaigns</a><?php else: ?><a class="btn btn-ghost" href="#about">Learn more</a><?php endif; ?>
   </div>
  </div>
 </div>
 <div class="hero-strip"><div class="container">
  <div><b data-count="<?= (int)$stats['donations'] ?>">0</b><span>Donations</span></div>
  <div><b data-count="<?= (int)$stats['amount'] ?>" data-pre="₹">₹0</b><span>Raised</span></div>
  <div><b data-count="<?= (int)$stats['campaigns'] ?>">0</b><span>Campaigns</span></div>
 </div></div>
</header>

<main>
<?php foreach($sectionOrder as $section=>$order): ?>

<?php if($section==='about' && $show['about']): ?>
<section class="section" id="about"><div class="container">
 <div class="sec-head rv"><span class="tag">Who we are</span><h2>About <?= h($siteHeading) ?></h2><p><?= h($aboutSubtitle) ?></p></div>
 <div class="grid-2">
  <article class="card rv"><div class="ico">🎯</div><h3>Our mission</h3><p><?= h($missionText) ?></p></article>
  <article class="card rv"><div class="ico">🌟</div><h3>Our vision</h3><p><?= h($visionText) ?></p></article>
 </div>
 <div class="counters rv">
  <div class="counter"><b data-count="<?= $livesImpacted ?>" data-suf="+">0</b><span>Lives impacted</span></div>
  <div class="counter"><b data-count="<?= $projectsCompleted ?>" data-suf="+">0</b><span>Projects completed</span></div>
  <div class="counter"><b data-count="<?= $communitiesServed ?>" data-suf="+">0</b><span>Communities served</span></div>
  <div class="counter"><b data-count="<?= $yearsOfService ?>">0</b><span>Years of service</span></div>
 </div>
</div></section>
<?php endif; ?>

<?php if($section==='stats' && $show['stats']): ?>
<section class="band"><div class="container">
 <div class="band-grid rv">
  <div><b data-count="<?= (int)$stats['donations'] ?>">0</b><span>Donations received</span></div>
  <div><b data-count="<?= (int)$stats['amount'] ?>" data-pre="₹">₹0</b><span>Funds raised</span></div>
  <div><b data-count="<?= (int)$stats['partners'] ?>">0</b><span>Partners</span></div>
  <div><b data-count="<?= (int)$stats['campaigns'] ?>">0</b><span>Active campaigns</span></div>
 </div>
</div></section>
<?php endif; ?>

<?php if($section==='programs' && $show['programs']): ?>
<section class="section alt" id="campaigns"><div class="container">
 <div class="sec-head rv"><span class="tag">Make a difference</span><h2>Featured campaigns</h2><p>Every rupee goes towards a cause. Pick one and help us reach the goal.</p></div>
 <div class="camp-grid">
 <?php foreach($campaigns as $c):
   $target=(float)$c['TargetAmount']; $raised=(float)$c['CurrentAmount'];
   $pct=$target>0?min(100,round($raised/$target*100)):0; $left=max(0,$target-$raised);
   $hasImg=!empty($c['ImagePath'])&&isValidFilePath($c['ImagePath']);
   $desc=trim((string)$c['Description']); ?>
  <article class="camp rv">
   <?php if($hasImg): ?><img class="camp-img" loading="lazy" src="<?= h($c['ImagePath']) ?>" alt="<?= h($c['Title']) ?>"><?php else: ?><div class="camp-ph" aria-hidden="true">💝</div><?php endif; ?>
   <div class="camp-body">
    <h3><?= h($c['Title']) ?></h3>
    <div class="clamp"><?= h($desc) ?></div>
    <?php if(mb_strlen($desc)>160): ?><button class="more" type="button">Read more</button><?php endif; ?>
    <?php if($target>0): ?>
    <div class="bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"><i data-w="<?= $pct ?>"></i></div>
    <div class="camp-meta"><span><b>₹<?= inr($raised) ?></b> raised</span><span><?= $pct ?>% of ₹<?= inr($target) ?></span></div>
    <div class="camp-meta" style="margin-top:4px"><span>₹<?= inr($left) ?> to go</span></div>
    <?php endif; ?>
    <button class="btn btn-primary btn-block" style="margin-top:auto" data-donate data-cid="<?= (int)$c['CampaignID'] ?>" data-cname="<?= h($c['Title']) ?>">💝 Donate to this cause</button>
   </div>
  </article>
 <?php endforeach; ?>
 </div>
</div></section>
<?php endif; ?>

<?php if($section==='gallery' && $show['gallery']): ?>
<section class="section" id="gallery"><div class="container">
 <div class="sec-head rv"><span class="tag">In pictures</span><h2>Moments of change</h2><p>A glimpse of the work your support makes possible.</p></div>
 <div class="gal rv">
 <?php foreach($gallery as $g): if(empty($g['ImagePath'])) continue; ?>
  <button type="button" data-lb data-src="<?= h($g['ImagePath']) ?>" data-title="<?= h($g['Title']) ?>" data-desc="<?= h($g['Description']) ?>" data-cat="<?= h($g['Category']) ?>">
   <img loading="lazy" src="<?= h($g['ImagePath']) ?>" alt="<?= h($g['Title']) ?>">
   <span class="cap"><?= h($g['Title']) ?></span>
  </button>
 <?php endforeach; ?>
 </div>
</div></section>
<?php endif; ?>

<?php if($section==='partners' && $show['partners']): ?>
<section class="section alt" id="partners"><div class="container">
 <div class="sec-head rv"><span class="tag">Together</span><h2>Our partners</h2><p>Organisations and individuals who stand with us.</p></div>
</div>
 <div class="marq rv"><div class="marq-track">
 <?php for($rep=0;$rep<(count($partners)<5?4:2);$rep++): foreach($partners as $p): ?>
  <div class="partner" <?= ($rep>0)?'aria-hidden="true"':'' ?>>
   <div class="lg"><?php if(!empty($p['LogoPath'])&&isValidFilePath($p['LogoPath'])): ?><img loading="lazy" src="<?= h($p['LogoPath']) ?>" alt="<?= h($p['PartnerName']) ?>"><?php else: ?><div class="ini"><?= h(strtoupper(mb_substr($p['PartnerName'],0,2))) ?></div><?php endif; ?></div>
   <b><?= h($p['PartnerName']) ?></b><small><?= h($p['ContactInfo']) ?></small>
  </div>
 <?php endforeach; endfor; ?>
 </div></div>
</section>
<?php endif; ?>

<?php endforeach; ?>

<?php if($testimonials): ?>
<section class="section" id="stories"><div class="container">
 <div class="sec-head rv"><span class="tag">Stories</span><h2>Voices of our community</h2><p>What people say about working with us.</p></div>
 <div class="stories">
 <?php foreach($testimonials as $t): ?>
  <figure class="story rv">
   <p><?= h($t['Message']) ?></p>
   <figcaption class="who">
    <?php if(!empty($t['Photo'])&&isValidFilePath($t['Photo'])): ?><img loading="lazy" src="<?= h($t['Photo']) ?>" alt=""><?php else: ?><span class="av"><?= h(strtoupper(mb_substr($t['Name'],0,1))) ?></span><?php endif; ?>
    <span><b><?= h($t['Name']) ?></b><small><?= h($t['Position']) ?></small></span>
   </figcaption>
  </figure>
 <?php endforeach; ?>
 </div>
</div></section>
<?php endif; ?>
</main>

<footer class="footer" id="contact"><div class="container">
 <div class="f-grid">
  <div>
   <div class="f-brand"><?php if($hasLogo): ?><img src="<?= h($logoPath) ?>" alt=""><?php else: ?><span>🌊</span><?php endif; ?><span><?= h($siteHeading) ?></span></div>
   <p style="max-width:320px"><?= h(mb_substr($aboutSubtitle,0,150)) ?></p>
   <p style="margin-top:16px"><button class="btn btn-primary btn-sm" data-donate>💝 Donate now</button></p>
  </div>
  <div><h4>Quick links</h4><ul>
   <?php foreach($nav as [$id,$label]): ?><li><a href="#<?= $id ?>"><?= h($label) ?></a></li><?php endforeach; ?>
  </ul></div>
  <div><h4>Follow us</h4>
   <?php if($social): ?><div class="socials"><?php foreach($social as $name=>$url): ?><a href="<?= h($url) ?>" target="_blank" rel="noopener" aria-label="<?= h($name) ?>"><?= h($socialIcon[$name]??'↗') ?></a><?php endforeach; ?></div>
   <?php else: ?><p style="color:#94a3b8">Social links coming soon.</p><?php endif; ?>
  </div>
  <div><h4>Contact us</h4>
   <?php if(!empty($contact['Phone'])): ?><div class="f-line"><span>📞</span><a href="tel:<?= h(preg_replace('/[^0-9+]/','',$contact['Phone'])) ?>"><?= h($contact['Phone']) ?></a></div><?php endif; ?>
   <?php if(!empty($contact['Email'])): ?><div class="f-line"><span>✉️</span><a href="mailto:<?= h($contact['Email']) ?>"><?= h($contact['Email']) ?></a></div><?php endif; ?>
   <?php if(!empty($contact['Address'])): ?><div class="f-line"><span>📍</span><span><?= h($contact['Address']) ?></span></div><?php endif; ?>
   <?php if($upiId): ?><div class="f-line"><span>💳</span><span>UPI: <?= h($upiId) ?></span></div><?php endif; ?>
  </div>
 </div>
 <div class="f-bottom">&copy; <?= date('Y') ?> <?= h($siteHeading) ?>. All rights reserved.</div>
</div></footer>

<div class="fabs">
 <?php if($waNumber): ?><a class="fab wa" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" href="https://wa.me/<?= h($waNumber) ?>?text=<?= rawurlencode('Hello! I would like to know more about '.$siteHeading.'.') ?>">
  <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/></svg></a><?php endif; ?>
 <?php if(!empty($social['Instagram'])): ?><a class="fab ig" target="_blank" rel="noopener" aria-label="Instagram" href="<?= h($social['Instagram']) ?>">
  <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 5.675a4.162 4.162 0 100 8.324 4.162 4.162 0 000-8.324zm0 6.862a2.7 2.7 0 110-5.4 2.7 2.7 0 010 5.4zm5.338-7.87a.97.97 0 100-1.94.97.97 0 000 1.94z"/></svg></a><?php endif; ?>
</div>

<div class="dbar" id="dbar"><button class="btn btn-primary" data-donate>💝 Donate now</button></div>

<?php if($events): ?>
<aside class="event" id="event" aria-label="Upcoming event">
 <button class="x" type="button" id="eventX" aria-label="Close">&times;</button>
 <small>📅 Upcoming event</small>
 <h4><?= h($events['Title']) ?></h4>
 <p>🗓️ <?= h(date('D, d M Y',strtotime($events['EventDate']))) ?></p>
 <?php if($events['Location']): ?><p>📍 <?= h($events['Location']) ?></p><?php endif; ?>
 <?php if($events['Description']): ?><p style="margin-top:6px"><?= h(mb_substr($events['Description'],0,110)) ?><?= mb_strlen($events['Description'])>110?'…':'' ?></p><?php endif; ?>
 <button class="btn btn-primary btn-sm" style="margin-top:12px" data-donate>✨ Support this</button>
</aside>
<?php endif; ?>

<!-- Donate -->
<div class="overlay" id="donate" role="dialog" aria-modal="true" aria-labelledby="dTitle">
 <div class="sheet">
  <button class="x" type="button" data-close aria-label="Close">&times;</button>
  <h2 id="dTitle">💝 Make a donation</h2>
  <p class="for">Donating to: <b id="cname">General fund</b></p>
  <div class="err<?= $formError?' show':'' ?>" id="err" role="alert"><?= h($formError) ?></div>

  <form method="POST" id="dform" novalidate>
   <input type="hidden" name="campaign_id" id="cid" value="">
   <input type="hidden" name="razorpay_payment_id" id="rzpid" value="" disabled>
   <input type="hidden" name="qr_donate" id="qrflag" value="1" disabled>

   <span class="lbl">Choose an amount (₹)</span>
   <div class="chips" id="chips">
    <?php foreach([500,1000,2500,5000] as $a): ?><button type="button" class="chip" data-amt="<?= $a ?>">₹<?= inr($a) ?></button><?php endforeach; ?>
   </div>
   <input class="fld" style="margin-top:10px;text-align:center;font-weight:700;font-size:1.15rem" type="number" inputmode="numeric" min="1" max="1000000" name="amount" id="amt" placeholder="Or enter any amount" required>

   <span class="lbl">Your details</span>
   <div class="two">
    <input class="fld" type="text" name="name" id="dn" placeholder="Full name" autocomplete="name" required>
    <input class="fld" type="tel" name="phone" id="dp" placeholder="Phone number" autocomplete="tel" inputmode="tel" required>
   </div>
   <input class="fld" style="margin-top:12px" type="email" name="email" id="de" placeholder="Email (for your receipt)" autocomplete="email" required>
   <textarea class="fld" style="margin-top:12px" name="message" id="dm" rows="2" placeholder="Message (optional)"></textarea>

   <div class="tabs" role="tablist">
    <button type="button" class="tab on" data-tab="rzp" role="tab">💳 Card / UPI / NetBanking</button>
    <?php if($hasQr||$upiId): ?><button type="button" class="tab" data-tab="qr" role="tab">📷 Scan UPI QR</button><?php endif; ?>
   </div>

   <div class="pane on" id="p-rzp">
    <button type="button" class="btn btn-primary btn-block" id="payBtn" style="padding:16px;font-size:1.05rem">Pay securely</button>
    <p class="secure">🔒 Secured by Razorpay. We never see your card details.</p>
   </div>

   <?php if($hasQr||$upiId): ?>
   <div class="pane" id="p-qr">
    <div class="qr">
     <?php if($hasQr): ?><img src="<?= h($qrCodeModal) ?>" alt="UPI QR code"><?php endif; ?>
     <p style="color:var(--muted);font-size:.93rem">Scan with any UPI app and pay <b id="qrAmt">the amount above</b></p>
     <?php if($upiId): ?><div class="upi"><span id="upiId"><?= h($upiId) ?></span><button type="button" id="copyUpi">Copy</button></div>
     <a class="btn btn-outline btn-sm" id="upiLink" href="#" style="display:none;margin-bottom:6px">Open in UPI app</a><?php endif; ?>
    </div>
    <input class="fld" style="margin-top:6px" type="text" name="utr" id="utr" placeholder="UPI reference / UTR number (optional)">
    <button type="submit" class="btn btn-primary btn-block" id="qrBtn" style="margin-top:12px;padding:16px">✅ I've paid — submit</button>
    <p class="secure">Our team verifies QR payments manually before they appear in the totals.</p>
   </div>
   <?php endif; ?>
  </form>
 </div>
</div>

<div class="overlay lb" id="lb" role="dialog" aria-modal="true" aria-label="Image viewer">
 <div class="lb-in">
  <button class="x" type="button" data-close aria-label="Close">&times;</button>
  <button class="nv l" type="button" id="lbPrev" aria-label="Previous">‹</button>
  <img id="lbImg" src="" alt="">
  <h3 id="lbT"></h3><p id="lbD"></p>
  <button class="nv r" type="button" id="lbNext" aria-label="Next">›</button>
 </div>
</div>

<div class="toast" id="toast" role="status"></div>

<script src="https://checkout.razorpay.com/v1/checkout.js" async></script>
<script>
(function(){
const $=id=>document.getElementById(id), qa=s=>[...document.querySelectorAll(s)];
const CFG=<?= json_encode([
    'key'=>$razorpayKeyId,'name'=>$siteHeading,'logo'=>$logoUrl,'color'=>$primaryColor,
    'upi'=>$upiId,'donated'=>$donatedFlag,'error'=>$formError!=='','hasEvent'=>(bool)$events,
], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
const inr=n=>Number(n||0).toLocaleString('en-IN');
const store={get(k){try{return localStorage.getItem(k)}catch(e){return null}},set(k,v){try{localStorage.setItem(k,v)}catch(e){}}};

/* nav */
const nav=$('nav'), menu=$('menu'), burger=$('burger'), dbar=$('dbar');
function onScroll(){
  nav.classList.toggle('scrolled',scrollY>40);
  dbar.classList.toggle('show',scrollY>420);
}
addEventListener('scroll',onScroll,{passive:true}); onScroll();
burger.addEventListener('click',()=>{const o=menu.classList.toggle('open');burger.setAttribute('aria-expanded',o);burger.textContent=o?'✕':'☰';nav.classList.add('scrolled')});
menu.addEventListener('click',e=>{if(e.target.closest('a')){menu.classList.remove('open');burger.textContent='☰';onScroll()}});

/* reveal + counters + progress bars */
function countUp(el){
  const t=+el.dataset.count||0, pre=el.dataset.pre||'', suf=el.dataset.suf||'', d=1400, t0=performance.now();
  (function f(n){const p=Math.min(1,(n-t0)/d), v=Math.round(t*(1-Math.pow(1-p,3)));el.textContent=pre+inr(v)+suf;if(p<1)requestAnimationFrame(f)})(t0);
}
const io=new IntersectionObserver(es=>es.forEach(e=>{
  if(!e.isIntersecting)return; const el=e.target;
  el.classList.add('in');
  el.querySelectorAll('[data-count]').forEach(countUp); if(el.dataset.count)countUp(el);
  el.querySelectorAll('[data-w]').forEach(b=>b.style.width=b.dataset.w+'%');
  io.unobserve(el);
}),{threshold:.15});
qa('.rv,.hero-strip').forEach(el=>io.observe(el));

/* campaign read-more */
qa('.more').forEach(b=>b.addEventListener('click',()=>{const c=b.previousElementSibling;const o=c.classList.toggle('open');b.textContent=o?'Show less':'Read more'}));

/* overlays */
let lastFocus=null;
function openO(el){lastFocus=document.activeElement;el.classList.add('open');document.body.style.overflow='hidden'}
function closeO(el){el.classList.remove('open');if(!qa('.overlay.open').length)document.body.style.overflow='';lastFocus&&lastFocus.focus&&lastFocus.focus()}
qa('.overlay').forEach(o=>{o.addEventListener('click',e=>{if(e.target===o||e.target.closest('[data-close]'))closeO(o)})});
addEventListener('keydown',e=>{
  if(e.key==='Escape')qa('.overlay.open').forEach(closeO);
  if($('lb').classList.contains('open')){if(e.key==='ArrowRight')lbGo(1);if(e.key==='ArrowLeft')lbGo(-1)}
});

/* donate */
const donate=$('donate'), amt=$('amt'), err=$('err');
function showErr(m){err.textContent=m;err.classList.add('show');err.scrollIntoView({block:'nearest'})}
function setAmt(v){
  amt.value=v||'';
  qa('.chip').forEach(c=>c.classList.toggle('on',+c.dataset.amt===+v));
  refreshAmt();
}
function refreshAmt(){
  const v=+amt.value||0;
  $('payBtn').textContent=v>0?'Pay ₹'+inr(v)+' securely':'Pay securely';
  const q=$('qrAmt'); if(q)q.textContent=v>0?'₹'+inr(v):'the amount above';
  const l=$('upiLink'); if(l&&CFG.upi){ if(v>0){l.href='upi://pay?pa='+encodeURIComponent(CFG.upi)+'&pn='+encodeURIComponent(CFG.name)+'&am='+v+'&cu=INR&tn='+encodeURIComponent('Donation');l.style.display='inline-flex'}else l.style.display='none'; }
}
function openDonate(cid,cname){
  $('cid').value=cid||''; $('cname').textContent=cname||'General fund';
  menu.classList.remove('open'); burger.textContent='☰';
  openO(donate); setTimeout(()=>amt.focus({preventScroll:true}),200);
}
document.addEventListener('click',e=>{
  const b=e.target.closest('[data-donate]'); if(!b)return; e.preventDefault();
  openDonate(b.dataset.cid,b.dataset.cname);
});
qa('.chip').forEach(c=>c.addEventListener('click',()=>setAmt(c.dataset.amt)));
amt.addEventListener('input',()=>{qa('.chip').forEach(c=>c.classList.toggle('on',+c.dataset.amt===+amt.value));refreshAmt()});
qa('.tab').forEach(t=>t.addEventListener('click',()=>{
  qa('.tab').forEach(x=>x.classList.toggle('on',x===t));
  qa('.pane').forEach(p=>p.classList.toggle('on',p.id==='p-'+t.dataset.tab));
  err.classList.remove('show');
}));
const cu=$('copyUpi'); if(cu)cu.addEventListener('click',()=>{
  const v=$('upiId').textContent;
  (navigator.clipboard?navigator.clipboard.writeText(v):Promise.reject()).then(()=>{cu.textContent='Copied ✓'}).catch(()=>{const r=document.createRange();r.selectNode($('upiId'));getSelection().removeAllRanges();getSelection().addRange(r);cu.textContent='Select & copy'});
  setTimeout(()=>cu.textContent='Copy',2000);
});
function validate(){
  const n=$('dn').value.trim(), p=$('dp').value.replace(/\D/g,''), e=$('de').value.trim(), v=+amt.value;
  if(!(v>=1)){showErr('Please choose or enter a donation amount.');amt.focus();return null}
  if(v>1000000){showErr('Maximum online donation is ₹10,00,000.');return null}
  if(n.length<2){showErr('Please enter your full name.');$('dn').focus();return null}
  if(p.length<10){showErr('Please enter a valid 10-digit phone number.');$('dp').focus();return null}
  if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(e)){showErr('Please enter a valid email address.');$('de').focus();return null}
  err.classList.remove('show'); return {n,p,e,v};
}
$('payBtn').addEventListener('click',()=>{
  const d=validate(); if(!d)return;
  if(typeof Razorpay==='undefined'){showErr('Online payment is not available right now. Please use the UPI QR option.');return}
  const form=$('dform');
  new Razorpay({
    key:CFG.key, amount:Math.round(d.v*100), currency:'INR', name:CFG.name, description:'Donation', image:CFG.logo||undefined,
    prefill:{name:d.n,email:d.e,contact:d.p}, theme:{color:CFG.color},
    handler:r=>{ const f=$('rzpid'); f.disabled=false; f.value=r.razorpay_payment_id; $('qrflag').disabled=true; $('payBtn').disabled=true; $('payBtn').textContent='Confirming your payment…'; form.submit() }
  }).open();
});
$('dform').addEventListener('submit',e=>{
  if(!$('rzpid').value){ // QR path
    const d=validate(); if(!d){e.preventDefault();return}
    $('qrflag').disabled=false; $('qrBtn').disabled=true; $('qrBtn').textContent='Submitting…';
  }
});

/* lightbox */
const items=qa('[data-lb]'); let li=0;
function lbShow(i){li=(i+items.length)%items.length;const b=items[li];$('lbImg').src=b.dataset.src;$('lbImg').alt=b.dataset.title;$('lbT').textContent=b.dataset.title||'';$('lbD').textContent=[b.dataset.cat,b.dataset.desc].filter(Boolean).join(' · ')}
function lbGo(d){lbShow(li+d)}
items.forEach((b,i)=>b.addEventListener('click',()=>{lbShow(i);openO($('lb'))}));
$('lbPrev').addEventListener('click',()=>lbGo(-1)); $('lbNext').addEventListener('click',()=>lbGo(1));
let tx=0; $('lb').addEventListener('touchstart',e=>tx=e.touches[0].clientX,{passive:true});
$('lb').addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-tx;if(Math.abs(dx)>50)lbGo(dx<0?1:-1)});

/* event card */
if(CFG.hasEvent){
  const ev=$('event');
  if(!store.get('eventClosed')) setTimeout(()=>ev.classList.add('open'),2500);
  $('eventX').addEventListener('click',()=>{ev.classList.remove('open');store.set('eventClosed','1')});
}

/* thank-you / error state */
function toast(m){const t=$('toast');t.textContent=m;t.classList.add('open');setTimeout(()=>t.classList.remove('open'),7000)}
if(CFG.donated===1){toast('💖 Thank you! Your donation was received successfully.')}
if(CFG.donated===2){toast('🙏 Thank you! Your donation is received and will appear after verification.')}
if(CFG.donated)history.replaceState(null,'',location.pathname+'#home');
if(CFG.error){openO(donate)}
refreshAmt();
})();
</script>
</body>
</html>

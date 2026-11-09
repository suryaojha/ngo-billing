<?php
require_once 'config.php';

// Safe file path validation function
function isValidFilePath($path) {
    if (empty($path)) return false;
    $realPath = realpath($path);
    $uploadsDir = realpath('uploads');
    return $realPath && $uploadsDir && strpos($realPath, $uploadsDir) === 0;
}

// Razorpay payment handler
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['razorpay_payment_id'])){
    try{
        $stmt=$pdo->prepare("INSERT INTO Donations(DonorName,Email,Phone,Amount,CampaignID,Message,PaymentMethod,TransactionID,Status)VALUES(?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_POST['donor_name'],
            $_POST['donor_email'],
            $_POST['donor_phone'],
            (float)$_POST['amount'],
            !empty($_POST['campaign_id'])?(int)$_POST['campaign_id']:null,
            $_POST['message']??'',
            'Razorpay',
            $_POST['razorpay_payment_id'],
            'Approved'
        ]);
        if(!empty($_POST['campaign_id'])){
            $pdo->prepare("UPDATE Campaigns SET CurrentAmount=CurrentAmount+? WHERE CampaignID=?")->execute([(float)$_POST['amount'],(int)$_POST['campaign_id']]);
        }
        $success="Thank you! Your donation has been processed successfully.";
    }catch(Exception $e){
        $error="Payment processing failed. Please try again.";
    }
}

// QR code donation handler
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['qr_donate'])){
    if(empty($_POST['amount'])||!is_numeric($_POST['amount'])||$_POST['amount']<=0){
        $error="Please enter a valid donation amount.";
    }elseif(empty($_POST['name'])||empty($_POST['email'])||empty($_POST['phone'])){
        $error="Please fill in all required fields.";
    }else{
        try{
            $stmt=$pdo->prepare("INSERT INTO Donations(DonorName,Email,Phone,Amount,CampaignID,Message,PaymentMethod,Status)VALUES(?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $_POST['name'],
                $_POST['email'],
                $_POST['phone'],
                (float)$_POST['amount'],
                !empty($_POST['campaign_id'])?(int)$_POST['campaign_id']:null,
                $_POST['message']??'',
                'QR Code',
                'Pending'
            ]);
            $success="Thank you! Your donation request has been submitted for verification.";
        }catch(Exception $e){
            $error="Submission failed. Please try again.";
        }
    }
}

try{
    $contact=$pdo->query("SELECT * FROM ContactInfo ORDER BY ContactID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $themeSettings=$pdo->query("SELECT * FROM ThemeSettings ORDER BY SettingID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $campaigns=$pdo->query("SELECT * FROM Campaigns WHERE IsActive=1 AND IsFeatured=1 ORDER BY CreatedDate DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    $testimonials=$pdo->query("SELECT * FROM Testimonials WHERE IsActive=1 ORDER BY DisplayOrder,CreatedDate DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $gallery=$pdo->query("SELECT * FROM Gallery WHERE IsActive=1 ORDER BY UploadDate DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    $partners=$pdo->query("SELECT * FROM Partners WHERE IsActive=1 ORDER BY DisplayOrder,CreatedDate DESC")->fetchAll(PDO::FETCH_ASSOC);
    $events=$pdo->query("SELECT * FROM Events WHERE IsActive=1 AND ShowPopup=1 AND EventDate>=CURDATE() ORDER BY EventDate ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $logoPath=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='LogoPath'")->fetchColumn();
    $heroVideo=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='HeroVideoPath'")->fetchColumn();
    $bgPath=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='BackgroundPath'")->fetchColumn();
    $siteHeading=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SiteHeading'")->fetchColumn();
    $primaryColorAdmin=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='PrimaryColor'")->fetchColumn();
    $primaryColor=$primaryColorAdmin?:($themeSettings['BackgroundColor']??'#667eea');
    $secondaryColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SecondaryColor'")->fetchColumn()??'#764ba2';
    $gradientType=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='GradientType'")->fetchColumn()??'linear';
    $gradientDirection=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='GradientDirection'")->fetchColumn()??'135deg';
    $fontColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='FontColor'")->fetchColumn()??'#333333';
    $qrCodeModal=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='QRCodePath'")->fetchColumn()??'';
    $upiId=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='UpiId'")->fetchColumn()??'reliefwave@paytm';
    $aboutSubtitle=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='AboutSubtitle'")->fetchColumn()??'Transforming lives through compassion, dedication, and sustainable impact across India';
    $missionText=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='MissionText'")->fetchColumn()??'To empower underprivileged communities through education, healthcare, and sustainable development programs that create lasting positive change.';
    $visionText=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='VisionText'")->fetchColumn()??'A world where everyone has access to opportunities, dignity, and the chance to build a better future for themselves and their families.';
    $livesImpacted=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='LivesImpacted'")->fetchColumn()??'1000';
    $projectsCompleted=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='ProjectsCompleted'")->fetchColumn()??'50';
    $communitiesServed=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='CommunitiesServed'")->fetchColumn()??'25';
    $yearsOfService=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='YearsOfService'")->fetchColumn()??'5';
    $heroFontColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='HeroFontColor'")->fetchColumn()??'#ffffff';
    $bgOpacity=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='ImageOpacity'")->fetchColumn()??'0.3';
    $sectionOrder=[
        'about'=>$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_about'")->fetchColumn()??1,
        'stats'=>$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_stats'")->fetchColumn()??2,
        'programs'=>$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_programs'")->fetchColumn()??3,
        'gallery'=>$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_gallery'")->fetchColumn()??4,
        'partners'=>$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_partners'")->fetchColumn()??5
    ];
    asort($sectionOrder);
    $stats=$pdo->query("SELECT 
        (SELECT COUNT(*) FROM Donations WHERE Status='Approved' AND IsActive=1) as donations,
        (SELECT IFNULL(SUM(Amount),0) FROM Donations WHERE Status='Approved' AND IsActive=1) as amount,
        (SELECT COUNT(*) FROM Partners WHERE IsActive=1) as partners,
        (SELECT COUNT(*) FROM Campaigns WHERE IsActive=1) as campaigns")->fetch(PDO::FETCH_ASSOC);
}catch(Exception $e){
    $contact=['Phone'=>'+91 98765 43210','Email'=>'info@reliefwave.org','WhatsAppNumber'=>'919876543210','Address'=>'Mumbai, India'];
    $themeSettings=['BackgroundColor'=>'#667eea','HeroTitle'=>'Together for Hope, Together for Change','HeroSubtitle'=>'Empowering communities across India','ShowAboutSection'=>1,'ShowStatsSection'=>1,'ShowProgramsSection'=>1,'ShowGallerySection'=>1,'ShowPartnersSection'=>1];
    $campaigns=[];$testimonials=[];$gallery=[];$partners=[];$events=null;$logoPath='';$heroVideo='';$bgPath='';$siteHeading='Relief Wave Foundation';
    $primaryColor='#667eea';$secondaryColor='#764ba2';$gradientType='linear';$gradientDirection='135deg';$fontColor='#333333';
    $stats=['donations'=>0,'amount'=>0,'partners'=>0,'campaigns'=>0];
    $qrCodeModal='';
    $upiId='reliefwave@paytm';
    $aboutSubtitle='Transforming lives through compassion, dedication, and sustainable impact across India';
    $sectionOrder=['about'=>1,'stats'=>2,'programs'=>3,'gallery'=>4,'partners'=>5];
    $missionText='To empower underprivileged communities through education, healthcare, and sustainable development programs that create lasting positive change.';
    $visionText='A world where everyone has access to opportunities, dignity, and the chance to build a better future for themselves and their families.';
    $livesImpacted='1000';$projectsCompleted='50';$communitiesServed='25';$yearsOfService='5';
}

$gradient=$gradientType==='solid'?$primaryColor:($gradientType==='radial'?"radial-gradient(circle, $primaryColor 0%, $secondaryColor 100%)":"linear-gradient($gradientDirection, $primaryColor 0%, $secondaryColor 100%)");

// Razorpay credentials
$razorpayKeyId='rzp_live_RygDIfainScYaz';
$razorpayKeySecret='pWH09E0gWKbuU51ZIWopuw2o';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<title>Relief Wave Foundation - Together for Hope</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;line-height:1.6;color:#333;overflow-x:hidden}
.hero{min-height:100vh;background:<?= $gradient ?>;<?php if($bgPath && $bgPath!='' && isValidFilePath($bgPath)): ?>background-image:url('<?= htmlspecialchars($bgPath) ?>?v=<?= time() ?>');background-blend-mode:overlay;background-size:cover;background-position:center;background-attachment:fixed;<?php endif; ?>position:relative;display:flex;align-items:center;justify-content:center;text-align:center;color:<?= $heroFontColor ?>}
.hero::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,<?= $bgOpacity ?>);z-index:1}
.hero-video{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:0}
.hero-content{position:relative;z-index:2;max-width:800px;padding:0 20px}
.hero h1{font-size:3.5rem;margin-bottom:20px;font-weight:700;text-shadow:2px 2px 4px rgba(0,0,0,0.5);animation:fadeInUp 1s ease-out}
.hero p{font-size:1.3rem;margin-bottom:30px;text-shadow:1px 1px 2px rgba(0,0,0,0.5);animation:fadeInUp 1s ease-out 0.3s both}
.btn{display:inline-block;padding:15px 35px;background:<?= $primaryColor ?>;color:#fff;text-decoration:none;border-radius:50px;font-weight:600;transition:all 0.3s;box-shadow:0 5px 15px rgba(0,0,0,0.3);animation:fadeInUp 1s ease-out 0.6s both;border:none;cursor:pointer}
.btn:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(0,0,0,0.4);background:<?= $secondaryColor ?>}
.navbar{position:fixed;top:0;width:100%;background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);z-index:1000;padding:15px 0;transition:all 0.3s}
.navbar.scrolled{background:rgba(255,255,255,0.98);box-shadow:0 2px 20px rgba(0,0,0,0.1)}
.nav-container{max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;padding:0 20px}
.logo{font-size:1.8rem;font-weight:700;display:flex;align-items:center;gap:10px}
.nav-links{display:flex;list-style:none;gap:30px}
.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color 0.3s}
.nav-links a:hover{color:<?= $primaryColor ?>}
.section{padding:80px 0;position:relative}
.section::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(45deg,transparent 49%,rgba(255,255,255,0.03) 50%,transparent 51%);pointer-events:none}
.parallax-bg{background-attachment:fixed;background-size:cover;background-position:center}
.floating-shapes{position:absolute;width:100%;height:100%;overflow:hidden;pointer-events:none}
.shape{position:absolute;background:<?= $gradient ?>;opacity:0.05;border-radius:50%;animation:floatShapes 15s infinite linear}
.shape:nth-child(1){width:80px;height:80px;top:10%;left:10%;animation-delay:0s}
.shape:nth-child(2){width:120px;height:120px;top:60%;right:15%;animation-delay:5s}
.shape:nth-child(3){width:60px;height:60px;bottom:20%;left:20%;animation-delay:10s}
@keyframes floatShapes{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-30px) rotate(180deg)}}
.glow-effect{box-shadow:0 0 30px rgba(<?= hexdec(substr($primaryColor,1,2)) ?>,<?= hexdec(substr($primaryColor,3,2)) ?>,<?= hexdec(substr($primaryColor,5,2)) ?>,0.3)}
.text-shimmer{background:linear-gradient(45deg,<?= $primaryColor ?> 30%,<?= $secondaryColor ?> 50%,<?= $primaryColor ?> 70%);background-size:200% 200%;animation:shimmer 3s ease-in-out infinite;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
@keyframes shimmer{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
.typewriter{overflow:hidden;border-right:3px solid <?= $primaryColor ?>;white-space:nowrap;animation:typing 4s steps(40,end),blink 1s infinite}
@keyframes typing{from{width:0}to{width:100%}}
@keyframes blink{50%{border-color:transparent}}
.scroll-reveal{opacity:0;transform:translateY(50px);transition:all 0.8s ease}
.scroll-reveal.revealed{opacity:1;transform:translateY(0)}
.magnetic-btn{transition:all 0.3s cubic-bezier(0.25,0.46,0.45,0.94)}
.magnetic-btn:hover{transform:translateY(-8px) scale(1.05);box-shadow:0 15px 35px rgba(0,0,0,0.2)}
.ripple-effect{position:relative;overflow:hidden}
.ripple-effect::after{content:'';position:absolute;top:50%;left:50%;width:0;height:0;border-radius:50%;background:rgba(255,255,255,0.5);transform:translate(-50%,-50%);transition:width 0.6s,height 0.6s}
.ripple-effect:active::after{width:300px;height:300px}
.about-enhanced::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="%23<?= substr($primaryColor,1) ?>" opacity="0.1"/><circle cx="80" cy="80" r="3" fill="%23<?= substr($secondaryColor,1) ?>" opacity="0.1"/></svg>');animation:float 20s ease-in-out infinite}
.mission-vision-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;margin:40px 0}
.mission-card,.vision-card{background:#fff;padding:40px 30px;border-radius:20px;box-shadow:0 15px 35px rgba(0,0,0,0.1);position:relative;overflow:hidden;transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275)}
.mission-card:hover,.vision-card:hover{transform:translateY(-10px);box-shadow:0 25px 50px rgba(0,0,0,0.15)}
.mission-card::before{content:'🎯';position:absolute;top:20px;right:20px;font-size:3rem;opacity:0.1}
.vision-card::before{content:'🌟';position:absolute;top:20px;right:20px;font-size:3rem;opacity:0.1}
.card-icon{width:60px;height:60px;background:<?= $gradient ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;margin-bottom:20px}
.card-title{font-size:1.5rem;font-weight:700;color:#333;margin-bottom:15px}
.card-text{color:#666;line-height:1.7;font-size:1rem}
.impact-counters{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:25px;margin:50px 0}
.counter-item{text-align:center;padding:30px 20px;background:#fff;border-radius:15px;box-shadow:0 10px 25px rgba(0,0,0,0.1);transition:transform 0.3s}
.counter-item:hover{transform:translateY(-5px)}
.counter-number{font-size:3rem;font-weight:bold;color:<?= $primaryColor ?>;margin-bottom:10px;display:block}
.counter-label{font-size:1rem;color:#666;font-weight:500}
.cta-buttons{display:flex;gap:20px;justify-content:center;margin-top:40px;flex-wrap:wrap}
.cta-btn{padding:15px 30px;border:none;border-radius:50px;font-weight:600;text-decoration:none;transition:all 0.3s;cursor:pointer;font-size:1rem}
.cta-primary{background:<?= $gradient ?>;color:#fff;box-shadow:0 5px 15px rgba(0,0,0,0.2)}
.cta-primary:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(0,0,0,0.3)}
.cta-secondary{background:transparent;color:<?= $primaryColor ?>;border:2px solid <?= $primaryColor ?>}
.cta-secondary:hover{background:<?= $primaryColor ?>;color:#fff}
.container{max-width:1200px;margin:0 auto;padding:0 20px}
.section-title{text-align:center;font-size:2.5rem;margin-bottom:20px;color:#333}
.section-subtitle{text-align:center;font-size:1.1rem;color:#666;margin-bottom:60px;max-width:600px;margin-left:auto;margin-right:auto}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:30px;margin:60px 0}
.stat-card{background:#fff;padding:40px 20px;border-radius:15px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);transition:transform 0.3s}
.stat-card:hover{transform:translateY(-10px)}
.stat-number{font-size:3rem;font-weight:700;color:<?= $primaryColor ?>;margin-bottom:10px}
.stat-label{font-size:1.1rem;color:#666}
.campaigns-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px}
.campaign-card{background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.1);transition:transform 0.3s}
.campaign-card:hover{transform:translateY(-5px)}
.campaign-img{width:100%;height:200px;object-fit:cover}
.campaign-content{padding:25px}
.campaign-title{font-size:1.3rem;font-weight:600;margin-bottom:15px;color:#333}
.campaign-desc{color:#666;margin-bottom:20px;line-height:1.6}
.progress-bar{background:#f0f0f0;height:8px;border-radius:4px;overflow:hidden;margin-bottom:15px}
.progress-fill{background:<?= $gradient ?>;height:100%;border-radius:4px;transition:width 0.3s}
.campaign-stats{display:flex;justify-content:space-between;font-size:0.9rem;color:#666}
.testimonials-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px}
.testimonial-card{background:#fff;padding:30px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.1);position:relative}
.testimonial-card::before{content:'"';position:absolute;top:-10px;left:20px;font-size:4rem;color:<?= $primaryColor ?>;opacity:0.3}
.testimonial-text{font-style:italic;margin-bottom:20px;color:#555;line-height:1.7}
.testimonial-author{display:flex;align-items:center;gap:15px}
.author-img{width:50px;height:50px;border-radius:50%;object-fit:cover}
.author-info h4{color:#333;margin-bottom:5px}
.author-info p{color:#666;font-size:0.9rem}
.gallery-container{position:relative}
.gallery-grid{display:flex;overflow:hidden;gap:20px;padding:20px 0;scroll-behavior:smooth}
.gallery-track{display:flex;animation:slideLeft 40s linear infinite;gap:20px}
.gallery-item{min-width:250px;position:relative;border-radius:15px;overflow:hidden;cursor:pointer;transition:transform 0.3s;flex-shrink:0}
.gallery-item:hover{transform:scale(1.05)}
.gallery-img{width:100%;height:200px;object-fit:cover}
.partners-slider{overflow:hidden;position:relative;width:100%}
.partners-track{display:flex;animation:slideLeft 50s linear infinite;width:max-content}
.partner-item{min-width:200px;height:120px;display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 15px;opacity:0.8;transition:opacity 0.3s;background:#fff;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1);padding:15px;cursor:pointer}
.partner-item:hover{opacity:1;transform:translateY(-5px)}
.partner-logo{max-width:120px;max-height:90px;object-fit:contain;margin-bottom:10px}
.partner-name{font-size:0.9rem;font-weight:600;color:#333;text-align:center}
.partner-profession{font-size:0.8rem;color:#666;text-align:center}
.footer{background:#2c3e50;color:#fff;padding:50px 0 20px}
.footer-content{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:40px;margin-bottom:30px}
.footer-section h3{margin-bottom:20px;color:#fff}
.footer-section p,.footer-section a{color:#bdc3c7;text-decoration:none;line-height:1.8}
.footer-section a:hover{color:#fff}
.social-links{display:flex;gap:15px;margin-top:20px}
.social-link{width:40px;height:40px;background:#34495e;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;transition:background 0.3s}
.social-link:hover{background:<?= $primaryColor ?>}
.footer-bottom{text-align:center;padding-top:20px;border-top:1px solid #34495e;color:#95a5a6}
.whatsapp-float{position:fixed;bottom:20px;right:20px;width:70px;height:70px;background:#25d366;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 25px rgba(37,211,102,0.5);z-index:1000;cursor:pointer;transition:all 0.3s;animation:pulse 2s infinite}
.whatsapp-float:hover{transform:scale(1.15);box-shadow:0 12px 35px rgba(37,211,102,0.7)}
.whatsapp-float svg{width:35px;height:35px;fill:#fff}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:2000;align-items:center;justify-content:center;overflow-y:auto;padding:20px}
.modal-content{background:#fff;padding:40px;border-radius:20px;max-width:600px;width:90%;position:relative;animation:slideIn 0.3s;margin:auto;max-height:90vh;overflow-y:auto}
.close{position:absolute;top:15px;right:20px;font-size:35px;cursor:pointer;color:#999;z-index:1;background:#fff;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all 0.3s}
.close:hover{background:#f0f0f0;color:#333;transform:rotate(90deg)}
.form-group{margin:25px 0}
.form-group label{display:block;margin-bottom:10px;font-weight:600;color:#333;font-size:1rem}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:15px;border:2px solid #e0e0e0;border-radius:12px;font-size:1rem;transition:all 0.3s;background:#fafafa}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:<?= $primaryColor ?>;box-shadow:0 0 0 4px rgba(0,0,0,0.1);background:#fff;transform:translateY(-2px)}
.event-popup{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:25px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.2);max-width:350px;z-index:1500;animation:slideInCenter 0.5s}
.popup-close{position:absolute;top:10px;right:15px;cursor:pointer;font-size:20px;color:#999}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideIn{from{transform:translateY(-50px);opacity:0}to{transform:translateY(0);opacity:1}}
@keyframes slideInCenter{from{transform:translate(-50%,-50%) scale(0.8);opacity:0}to{transform:translate(-50%,-50%) scale(1);opacity:1}}
@keyframes slideLeft{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
.enhanced-card{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2)}
.gradient-text{background:<?= $gradient ?>;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.floating-elements::before{content:'';position:absolute;width:100px;height:100px;background:<?= $primaryColor ?>;opacity:0.1;border-radius:50%;top:10%;left:10%;animation:float 6s ease-in-out infinite}
.floating-elements::after{content:'';position:absolute;width:150px;height:150px;background:<?= $secondaryColor ?>;opacity:0.1;border-radius:50%;bottom:10%;right:10%;animation:float 8s ease-in-out infinite reverse}
@keyframes float{0%,100%{transform:translateY(0px)}50%{transform:translateY(-20px)}}
.section-divider{height:100px;background:<?= $gradient ?>;clip-path:polygon(0 0,100% 0,100% 70%,0 100%)}
.card-hover{transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275)}
.card-hover:hover{transform:translateY(-15px) scale(1.02);box-shadow:0 20px 40px rgba(0,0,0,0.15)}
.text-gradient{background:<?= $gradient ?>;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.btn-gradient{background:<?= $gradient ?>;position:relative;overflow:hidden}
.btn-gradient::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:left 0.5s}
.btn-gradient:hover::before{left:100%}
.gallery-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;width:50px;height:50px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:20px;color:#333;box-shadow:0 5px 15px rgba(0,0,0,0.2);transition:all 0.3s;z-index:10}
.gallery-nav:hover{background:#fff;transform:translateY(-50%) scale(1.1)}
.gallery-nav.left{left:10px}
.gallery-nav.right{right:10px}
@media(max-width:768px){
.hero h1{font-size:2.5rem}
.hero p{font-size:1.1rem}
.nav-links{display:none}
.stats-grid{grid-template-columns:repeat(2,1fr)}
.campaigns-grid,.testimonials-grid{grid-template-columns:1fr}
.footer-content{grid-template-columns:1fr}
.whatsapp-float{width:60px;height:60px;bottom:15px;right:15px}
.whatsapp-float svg{width:30px;height:30px}
.logo{font-size:1.4rem}
.section{padding:60px 0}
.modal-content{padding:25px;margin:10px}
.modal-content h2{font-size:1.5rem}
.modal-content div[style*="grid-template-columns"]{grid-template-columns:1fr !important}
.gallery-item{min-width:200px}
.partner-item{min-width:150px;height:100px;padding:10px}
.partner-logo{max-width:60px;max-height:45px}
.partner-name{font-size:0.8rem}
.partner-profession{font-size:0.7rem}
.event-popup{max-width:300px;padding:20px}
}
.image-modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:3000;align-items:center;justify-content:center;padding:20px}
.image-modal-content{max-width:90%;max-height:90%;background:#fff;border-radius:15px;overflow:hidden;position:relative}
.image-modal img{width:100%;max-height:70vh;object-fit:contain}
.image-modal-info{padding:20px;text-align:center}
.image-modal-info h3{margin-bottom:10px;color:#333}
.image-modal-info p{color:#666;margin-bottom:5px}
.image-modal .close{position:absolute;top:10px;right:15px;font-size:30px;color:#333;cursor:pointer;z-index:3001;background:#fff;width:35px;height:35px;border-radius:50%;display:flex;align-items:center;justify-content:center}
</style>
</head>
<body>

<nav class="navbar" id="navbar">
<div class="nav-container">
<div class="logo">
<?php if($logoPath && isValidFilePath($logoPath)): ?>
<img src="<?= $logoPath ?>" alt="<?= htmlspecialchars($siteHeading) ?>" style="height:45px;border-radius:8px">
<span style="color:<?= $fontColor ?>"><?= htmlspecialchars($siteHeading) ?></span>
<?php else: ?>
<span style="color:<?= $fontColor ?>">🌊 <?= htmlspecialchars($siteHeading) ?></span>
<?php endif; ?>
</div>
<ul class="nav-links">
<li><a href="#home">Home</a></li>
<li><a href="#about">About</a></li>
<li><a href="#campaigns">Campaigns</a></li>
<li><a href="#gallery">Gallery</a></li>
<li><a href="#contact">Contact</a></li>
<li><a href="#" onclick="openDonateModal()">Donate</a></li>
</ul>
</div>
</nav>

<section class="hero floating-elements" id="home">
<?php if($heroVideo && isValidFilePath($heroVideo)): ?>
<video class="hero-video" autoplay muted loop>
<source src="<?= $heroVideo ?>" type="video/mp4">
</video>
<?php endif; ?>
<div class="hero-content">
<h1><?= htmlspecialchars($themeSettings['HeroTitle']) ?></h1>
<p><?= htmlspecialchars($themeSettings['HeroSubtitle']) ?></p>
<a href="#" onclick="openDonateModal()" class="btn btn-gradient">💝 Donate Now</a>
</div>
</section>

<?php foreach($sectionOrder as $section=>$order): ?>
<?php if($section==='about' && $themeSettings['ShowAboutSection']): ?>
<section class="section about-enhanced scroll-reveal" id="about">
<div class="floating-shapes">
<div class="shape"></div>
<div class="shape"></div>
<div class="shape"></div>
</div>
<div class="container">
<h2 class="section-title text-shimmer">About <?= htmlspecialchars($siteHeading) ?></h2>
<p class="section-subtitle typewriter"><?= htmlspecialchars($aboutSubtitle) ?></p>

<div class="mission-vision-grid scroll-reveal">
<div class="mission-card glow-effect">
<div class="card-icon ripple-effect">🎯</div>
<h3 class="card-title">Our Mission</h3>
<p class="card-text"><?= htmlspecialchars($missionText) ?></p>
</div>
<div class="vision-card glow-effect">
<div class="card-icon ripple-effect">🌟</div>
<h3 class="card-title">Our Vision</h3>
<p class="card-text"><?= htmlspecialchars($visionText) ?></p>
</div>
</div>

<div class="impact-counters">
<div class="counter-item">
<span class="counter-number" data-target="<?= $livesImpacted ?>">0</span>
<div class="counter-label">Lives Impacted</div>
</div>
<div class="counter-item">
<span class="counter-number" data-target="<?= $projectsCompleted ?>">0</span>
<div class="counter-label">Projects Completed</div>
</div>
<div class="counter-item">
<span class="counter-number" data-target="<?= $communitiesServed ?>">0</span>
<div class="counter-label">Communities Served</div>
</div>
<div class="counter-item">
<span class="counter-number" data-target="<?= $yearsOfService ?>">0</span>
<div class="counter-label">Years of Service</div>
</div>
</div>

<div class="cta-buttons scroll-reveal">
<a href="#campaigns" class="cta-btn cta-primary magnetic-btn ripple-effect">💝 Join Our Mission</a>
<a href="#contact" class="cta-btn cta-secondary magnetic-btn ripple-effect">📞 Learn More</a>
</div>
</div>
</section>
<?php endif; ?>

<?php if($section==='stats' && $themeSettings['ShowStatsSection']): ?>
<section class="section">
<div class="container">
<div class="stats-grid">
<div class="stat-card">
<div class="stat-number"><?= $stats['donations'] ?></div>
<div class="stat-label">Donations</div>
</div>
<div class="stat-card">
<div class="stat-number">₹<?= number_format($stats['amount']) ?></div>
<div class="stat-label">Raised</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['partners'] ?></div>
<div class="stat-label">Partners</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['campaigns'] ?></div>
<div class="stat-label">Campaigns</div>
</div>
</div>
</div>
</section>
<?php endif; ?>

<?php if($section==='programs' && $campaigns && $themeSettings['ShowProgramsSection']): ?>
<section class="section" id="campaigns">
<div class="container">
<h2 class="section-title text-gradient">Featured Campaigns</h2>
<p class="section-subtitle">Join us in making a difference through our ongoing campaigns</p>
<div class="campaigns-grid">
<?php foreach($campaigns as $c): ?>
<div class="campaign-card enhanced-card card-hover">
<?php if($c['ImagePath']): ?>
<img src="<?= $c['ImagePath'] ?>" alt="<?= htmlspecialchars($c['Title']) ?>" class="campaign-img">
<?php endif; ?>
<div class="campaign-content">
<h3 class="campaign-title"><?= htmlspecialchars($c['Title']) ?></h3>
<p class="campaign-desc"><?= htmlspecialchars(substr($c['Description'],0,100)) ?>...</p>
<div class="progress-bar">
<div class="progress-fill" style="width:<?= $c['TargetAmount']>0?($c['CurrentAmount']/$c['TargetAmount']*100):0 ?>%"></div>
</div>
<div class="campaign-stats">
<span>₹<?= number_format($c['CurrentAmount']) ?> raised</span>
<span>₹<?= number_format($c['TargetAmount']) ?> goal</span>
</div>
<div style="margin-top:15px;font-size:0.9rem;color:#666">
<span>₹<?= number_format($c['TargetAmount']-$c['CurrentAmount']) ?> left to reach goal</span>
</div>
<button onclick="donateForCampaign(<?= $c['CampaignID'] ?>,'<?= htmlspecialchars($c['Title']) ?>')" class="btn" style="width:100%;margin-top:15px;padding:12px;font-size:1rem">💝 Donate Now</button>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php if($section==='gallery' && $gallery && $themeSettings['ShowGallerySection']): ?>
<section class="section" id="gallery">
<div class="container">
<h2 class="section-title text-gradient">Gallery</h2>
<p class="section-subtitle">Moments from our journey of making a difference</p>
<div class="gallery-container">
<div class="gallery-grid" id="galleryGrid">
<div class="gallery-track">
<?php foreach(array_merge($gallery,$gallery) as $g): ?>
<div class="gallery-item" onclick="openImageModal('<?= $g['ImagePath'] ?>','<?= htmlspecialchars($g['Title']) ?>','<?= htmlspecialchars($g['Description']) ?>','<?= htmlspecialchars($g['Category']) ?>')">
<img src="<?= $g['ImagePath'] ?>" alt="<?= htmlspecialchars($g['Title']) ?>" class="gallery-img">
</div>
<?php endforeach; ?>
</div>
</div>
</div>
</div>
</section>
<?php endif; ?>

<?php if($section==='partners' && $partners && $themeSettings['ShowPartnersSection']): ?>
<section class="section" style="background:#f8f9fa">
<div class="container">
<h2 class="section-title text-gradient">Our Partners</h2>
<div class="partners-slider">
<div class="partners-track">
<?php foreach($partners as $p): ?>
<div class="partner-item" onclick="openImageModal('<?= htmlspecialchars($p['LogoPath']) ?>','<?= htmlspecialchars($p['PartnerName']) ?>','<?= htmlspecialchars($p['ContactInfo']) ?>','Partner')">
<?php if($p['LogoPath'] && isValidFilePath($p['LogoPath'])): ?>
<img src="<?= htmlspecialchars($p['LogoPath']) ?>" alt="<?= htmlspecialchars($p['PartnerName']) ?>" class="partner-logo">
<?php else: ?>
<div style="width:120px;height:90px;background:<?= $primaryColor ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;margin-bottom:10px"><?= strtoupper(substr($p['PartnerName'],0,2)) ?></div>
<?php endif; ?>
<div class="partner-name"><?= htmlspecialchars($p['PartnerName']) ?></div>
<div class="partner-profession"><?= htmlspecialchars($p['ContactInfo']) ?></div>
</div>
<?php endforeach; ?>
<?php foreach($partners as $p): ?>
<div class="partner-item" onclick="openImageModal('<?= htmlspecialchars($p['LogoPath']) ?>','<?= htmlspecialchars($p['PartnerName']) ?>','<?= htmlspecialchars($p['ContactInfo']) ?>','Partner')">
<?php if($p['LogoPath'] && isValidFilePath($p['LogoPath'])): ?>
<img src="<?= htmlspecialchars($p['LogoPath']) ?>" alt="<?= htmlspecialchars($p['PartnerName']) ?>" class="partner-logo">
<?php else: ?>
<div style="width:120px;height:90px;background:<?= $primaryColor ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;margin-bottom:10px"><?= strtoupper(substr($p['PartnerName'],0,2)) ?></div>
<?php endif; ?>
<div class="partner-name"><?= htmlspecialchars($p['PartnerName']) ?></div>
<div class="partner-profession"><?= htmlspecialchars($p['ContactInfo']) ?></div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
</section>
<?php endif; ?>
<?php endforeach; ?>

<footer class="footer" id="contact">
<div class="container">
<div class="footer-content">
<div class="footer-section">
<h3>Contact Us</h3>
<p>📞 <?= htmlspecialchars($contact['Phone']) ?></p>
<p>✉️ <?= htmlspecialchars($contact['Email']) ?></p>
<p>📍 <?= htmlspecialchars($contact['Address']??'Mumbai, India') ?></p>
</div>
<div class="footer-section">
<h3>Quick Links</h3>
<p><a href="#home">Home</a></p>
<p><a href="#about">About</a></p>
<p><a href="#campaigns">Campaigns</a></p>
<p><a href="#gallery">Gallery</a></p>
</div>
<div class="footer-section">
<h3>Follow Us</h3>
<div class="social-links">
<a href="#" class="social-link">📘</a>
<a href="#" class="social-link">🐦</a>
<a href="#" class="social-link">📷</a>
<a href="#" class="social-link">💼</a>
</div>
</div>
</div>
<div class="footer-bottom">
<p>&copy; 2024 Relief Wave Foundation. All rights reserved.</p>
</div>
</div>
</footer>

<div class="whatsapp-float" onclick="openWhatsApp()">
<svg viewBox="0 0 24 24">
<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
</svg>
</div>

<?php if($contact['InstagramURL']): ?>
<div style="position:fixed;bottom:100px;right:20px;width:60px;height:60px;background:linear-gradient(45deg,#f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 25px rgba(188,24,136,0.5);z-index:1000;cursor:pointer;transition:all 0.3s;animation:pulse 2s infinite" onclick="openInstagram()">
<svg viewBox="0 0 24 24" style="width:30px;height:30px;fill:#fff">
<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
</svg>
</div>
<?php endif; ?>

<div class="modal" id="donateModal">
<div class="modal-content">
<span class="close" onclick="closeDonateModal()">&times;</span>
<h2 style="text-align:center;color:<?= $primaryColor ?>;margin-bottom:35px;font-size:2rem;font-weight:700">💝 Make a Donation</h2>
<?php if(isset($success)): ?>
<div style="background:#d4edda;color:#155724;padding:15px;border-radius:8px;margin-bottom:20px"><?= $success ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
<div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;margin-bottom:20px"><?= $error ?></div>
<?php endif; ?>

<div id="donationForm">
<input type="hidden" id="campaignId" value="">
<div class="form-group">
<label>🎯 Donating for: <span id="campaignName" style="color:<?= $primaryColor ?>;font-weight:bold">General Donation</span></label>
</div>
<div class="form-group">
<label>💰 Select Amount (₹)</label>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:15px">
<button type="button" onclick="setAmount(500)" class="btn-small" style="background:#e9ecef;color:#333;border:2px solid #ddd;padding:10px;border-radius:8px;cursor:pointer;font-weight:600">₹500</button>
<button type="button" onclick="setAmount(1000)" class="btn-small" style="background:#e9ecef;color:#333;border:2px solid #ddd;padding:10px;border-radius:8px;cursor:pointer;font-weight:600">₹1000</button>
<button type="button" onclick="setAmount(1500)" class="btn-small" style="background:#e9ecef;color:#333;border:2px solid #ddd;padding:10px;border-radius:8px;cursor:pointer;font-weight:600">₹1500</button>
</div>
<input type="number" id="amountInput" min="1" required placeholder="Enter custom amount" style="width:100%;padding:15px;border:2px solid #e0e0e0;border-radius:12px;font-size:1.1rem;text-align:center;font-weight:600">
</div>

<div id="donorDetails">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
<div class="form-group">
<label>👤 Full Name</label>
<input type="text" id="donorName" required placeholder="Enter your full name">
</div>
<div class="form-group">
<label>✉️ Email</label>
<input type="email" id="donorEmail" required placeholder="your@email.com">
</div>
</div>
<div class="form-group">
<label>📱 Phone</label>
<input type="tel" id="donorPhone" required placeholder="+91 98765 43210">
</div>
</div>
<div class="form-group">
<label>💬 Message (Optional)</label>
<textarea id="donorMessage" rows="3" placeholder="Share your thoughts or dedication..."></textarea>
</div>

<div style="text-align:center;margin:25px 0">
<button onclick="payWithRazorpay()" class="btn btn-gradient" style="width:100%;padding:18px;font-size:1.1rem;margin-bottom:15px">💳 Pay with Razorpay</button>
<div style="text-align:center;margin:15px 0;color:#666">OR</div>
<?php if($qrCodeModal && $qrCodeModal!='' && isValidFilePath($qrCodeModal)): ?>
<button type="button" onclick="showQRPayment()" style="background:#28a745;color:#fff;border:none;padding:15px 30px;border-radius:8px;cursor:pointer;font-weight:600;width:100%">📷 Pay via QR Code</button>
<?php endif; ?>
</div>
</div>

<!-- QR Payment Form -->
<form method="POST" id="qrPaymentForm" style="display:none">
<input type="hidden" name="campaign_id" id="qrCampaignId">
<div style="text-align:center;margin:20px 0">
<h4 style="color:<?= $primaryColor ?>;margin-bottom:15px">📷 Scan QR Code to Pay</h4>
<img src="<?= $qrCodeModal ?>" alt="Payment QR Code" style="max-width:200px;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.1)">
<p style="margin:15px 0;color:#666">Scan with any UPI app and pay ₹<span id="qrAmount">0</span></p>
</div>
<div class="form-group">
<label>👤 Full Name</label>
<input type="text" name="name" required placeholder="Enter your full name">
</div>
<div class="form-group">
<label>✉️ Email</label>
<input type="email" name="email" required placeholder="your@email.com">
</div>
<div class="form-group">
<label>📱 Phone</label>
<input type="tel" name="phone" required placeholder="+91 98765 43210">
</div>
<div class="form-group">
<label>💬 Message (Optional)</label>
<textarea name="message" rows="3" placeholder="Share your thoughts or dedication..."></textarea>
</div>
<input type="hidden" name="amount" id="qrAmountHidden">
<button type="submit" name="qr_donate" class="btn btn-gradient" style="width:100%;padding:18px;font-size:1.1rem;margin-top:10px">💖 Submit QR Payment</button>
<button type="button" onclick="showRazorpayPayment()" style="background:#6c757d;color:#fff;border:none;padding:12px;width:100%;border-radius:8px;margin-top:10px;cursor:pointer">← Back to Razorpay</button>
</form>
</div>
</div>

<?php if($events): ?>
<div class="event-popup" id="eventPopup">
<span class="popup-close" onclick="closeEventPopup()">&times;</span>
<h3 style="color:<?= $primaryColor ?>;margin-bottom:15px">📅 Upcoming Event</h3>
<h4><?= htmlspecialchars($events['Title']) ?></h4>
<p style="margin:10px 0;color:#666">📍 <?= htmlspecialchars($events['Location']) ?></p>
<p style="margin:10px 0;color:#666">🗓️ <?= date('M d, Y',strtotime($events['EventDate'])) ?></p>
<p style="margin:15px 0"><?= htmlspecialchars(substr($events['Description'],0,100)) ?>...</p>
<button onclick="openDonateModal();closeEventPopup()" style="background:<?= $primaryColor ?>;color:#fff;border:none;padding:12px 25px;border-radius:25px;cursor:pointer;transition:all 0.3s" onmouseover="this.style.background='<?= $secondaryColor ?>'" onmouseout="this.style.background='<?= $primaryColor ?>'">✨ Join Us</button>
</div>
<?php endif; ?>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<div class="image-modal" id="imageModal" onclick="closeImageModal()">
<div class="image-modal-content" onclick="event.stopPropagation()">
<span class="close" onclick="closeImageModal()">&times;</span>
<img id="modalImage" src="" alt="">
<div class="image-modal-info">
<h3 id="modalTitle"></h3>
<p id="modalDescription"></p>
<p id="modalCategory"></p>
</div>
</div>
</div>
<script>
function openDonateModal(){document.getElementById('donateModal').style.display='flex'}
function closeDonateModal(){document.getElementById('donateModal').style.display='none'}
function openWhatsApp(){window.open('https://wa.me/<?= $contact['WhatsAppNumber'] ?>?text=Hello! I would like to know more about Relief Wave Foundation.','_blank')}
function openInstagram(){window.open('<?= $contact['InstagramURL'] ?>','_blank')}
function closeEventPopup(){document.getElementById('eventPopup').style.display='none';localStorage.setItem('eventClosed','true')}

function payWithRazorpay(){
    const amount=document.getElementById('amountInput').value;
    const name=document.getElementById('donorName').value;
    const email=document.getElementById('donorEmail').value;
    const phone=document.getElementById('donorPhone').value;
    const message=document.getElementById('donorMessage').value;
    const campaignId=document.getElementById('campaignId').value;
    
    if(!amount||amount<=0){alert('Please enter donation amount');return;}
    if(!name||!email||!phone){alert('Please fill all required fields');return;}
    
    const options={
        key:'<?= $razorpayKeyId ?>',
        amount:amount*100,
        currency:'INR',
        name:'<?= htmlspecialchars($siteHeading) ?>',
        description:'Donation to Relief Wave Foundation',
        image:'<?= $logoPath ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' . ltrim($logoPath, '/') : '' ?>',
        handler:function(response){
            const form=document.createElement('form');
            form.method='POST';
            form.innerHTML=`
                <input type="hidden" name="razorpay_payment_id" value="${response.razorpay_payment_id}">
                <input type="hidden" name="donor_name" value="${name}">
                <input type="hidden" name="donor_email" value="${email}">
                <input type="hidden" name="donor_phone" value="${phone}">
                <input type="hidden" name="amount" value="${amount}">
                <input type="hidden" name="campaign_id" value="${campaignId}">
                <input type="hidden" name="message" value="${message}">
            `;
            document.body.appendChild(form);
            form.submit();
        },
        prefill:{
            name:name,
            email:email,
            contact:phone
        },
        theme:{
            color:'<?= $primaryColor ?>'
        }
    };
    const rzp=new Razorpay(options);
    rzp.open();
}

function showQRPayment(){
    const amount=document.getElementById('amountInput').value;
    if(!amount||amount<=0){alert('Please enter donation amount first');return;}
    
    document.getElementById('donationForm').style.display='none';
    document.getElementById('qrPaymentForm').style.display='block';
    document.getElementById('qrAmount').textContent=amount;
    document.getElementById('qrAmountHidden').value=amount;
    document.getElementById('qrCampaignId').value=document.getElementById('campaignId').value;
}

function showRazorpayPayment(){
    document.getElementById('donationForm').style.display='block';
    document.getElementById('qrPaymentForm').style.display='none';
}

function setAmount(amt){
    document.getElementById('amountInput').value=amt;
    document.querySelectorAll('.btn-small').forEach(b=>b.style.background='#e9ecef');
    event.target.style.background='<?= $primaryColor ?>';event.target.style.color='#fff';
}

function donateForCampaign(campaignId,campaignName){
    document.getElementById('campaignId').value=campaignId;
    document.getElementById('campaignName').textContent=campaignName;
    openDonateModal();
}

window.addEventListener('scroll',()=>{
const navbar=document.getElementById('navbar');
if(window.scrollY>50){navbar.classList.add('scrolled')}else{navbar.classList.remove('scrolled')}
});

<?php if($events): ?>
setTimeout(()=>{
if(!localStorage.getItem('eventClosed')){document.getElementById('eventPopup').style.display='block'}
},2000);
<?php endif; ?>

function animateCounters(){
document.querySelectorAll('.counter-number').forEach(counter=>{
const target=parseInt(counter.getAttribute('data-target'));
const increment=target/100;
let current=0;
const timer=setInterval(()=>{
current+=increment;
if(current>=target){
counter.textContent=target;
clearInterval(timer);
}else{
counter.textContent=Math.floor(current);
}
},20);
});
}
const aboutSection=document.getElementById('about');
if(aboutSection){
const observer=new IntersectionObserver((entries)=>{
if(entries[0].isIntersecting){
animateCounters();
observer.disconnect();
}
});
observer.observe(aboutSection);
}

function initScrollReveal(){
const reveals=document.querySelectorAll('.scroll-reveal');
const observer=new IntersectionObserver((entries)=>{
entries.forEach(entry=>{
if(entry.isIntersecting){
entry.target.classList.add('revealed');
}
});
},{threshold:0.1});
reveals.forEach(reveal=>observer.observe(reveal));
}
initScrollReveal();

document.addEventListener('click',(e)=>{
if(e.target===document.getElementById('donateModal')){closeDonateModal()}
});

function openImageModal(src,title,description,category){
if(!src || src==='') return;
document.getElementById('modalImage').src=src;
document.getElementById('modalImage').alt=title;
document.getElementById('modalTitle').textContent=title;
document.getElementById('modalDescription').textContent=description||'';
document.getElementById('modalCategory').textContent=category?`Category: ${category}`:'';
document.getElementById('imageModal').style.display='flex';
}

function closeImageModal(){
document.getElementById('imageModal').style.display='none';
}
</script>
</body>
</html>
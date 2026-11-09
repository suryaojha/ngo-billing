<?php
session_start();
require_once 'config.php';
require_once 'ui.php';     // shared helpers (pagination, escaping)
require_once 'xlsx.php';   // Excel export

// WHERE clause for the donations list/export: search (name/email/phone) + status
function donation_filter(array $g): array {
    $w = ['d.IsActive=1']; $p = [];
    $st = (string)($g['dstatus'] ?? '');
    if (in_array($st, ['Pending', 'Approved', 'Rejected'], true)) { $w[] = 'd.Status=?'; $p[] = $st; }
    $q = trim(preg_replace('/[^\x20-\x7E]/', '', (string)($g['dq'] ?? '')));
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $w[] = '(d.DonorName LIKE ? OR d.Email LIKE ? OR d.Phone LIKE ?)';
        array_push($p, $like, $like, $like);
    }
    return [' WHERE ' . implode(' AND ', $w), $p];
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])){
    try{
        $stmt=$pdo->prepare("SELECT * FROM Users WHERE Email=? AND IsActive=1");
        $stmt->execute([$_POST['email']]);
        $user=$stmt->fetch();
        if($user && ($_POST['password']===$user['Password'] || password_verify($_POST['password'],$user['Password']))){
            $_SESSION['admin']=$_POST['email'];
            header('Location: admin.php');
            exit;
        }else{
            $error="Invalid credentials";
        }
    }catch(Exception $e){
        error_log("Login error: " . $e->getMessage());
        $error="Database error. Please check configuration.";
    }
}

if(isset($_POST['logout'])){
    session_destroy();
    header('Location: admin.php');
    exit;
}

if(isset($_SESSION['admin'])){
    // ---- Excel export: ?export=donations (current filter) or ?export=backup (whole website) ----
    if(isset($_GET['export'])){
        try{
            $stamp=date('Y-m-d_Hi'); $sheets=[];
            $donationSheet=function(array $g) use ($pdo){
                [$where,$params]=donation_filter($g);
                $st=$pdo->prepare("SELECT d.*, c.Title AS CampaignTitle FROM Donations d LEFT JOIN Campaigns c ON c.CampaignID=d.CampaignID $where ORDER BY d.DonationDate DESC, d.DonationID DESC");
                $st->execute($params);
                $rows=[];
                foreach($st->fetchAll(PDO::FETCH_ASSOC) as $d){
                    $rows[]=[(int)$d['DonationID'],date('Y-m-d H:i',strtotime($d['DonationDate'])),$d['DonorName'],$d['Email'],$d['Phone'],
                        (float)$d['Amount'],$d['CampaignTitle']??'General',$d['PaymentMethod']??($d['DonationType']??''),$d['TransactionID']??'',$d['Status'],$d['Message']];
                }
                return ['headers'=>['ID','Date','Donor','Email','Phone','Amount (₹)','Campaign','Method','Reference','Status','Message'],'rows'=>$rows,'money'=>[5]];
            };
            if($_GET['export']==='donations'){
                $sheets['Donations']=$donationSheet($_GET);
                $file="donations_$stamp";
            }else{
                $sheets['Donations']=$donationSheet([]);
                // every other website table, as-is (users/passwords are never exported)
                foreach(['Campaigns'=>'CampaignID','Events'=>'EventID','Partners'=>'PartnerID','Testimonials'=>'TestimonialID','Gallery'=>'GalleryID','MediaGallery'=>'MediaID','TeamMembers'=>'TeamID','ContactInfo'=>'ContactID','AdminSettings'=>'SettingID'] as $tbl=>$pk){
                    try{
                        $data=$pdo->query("SELECT * FROM `$tbl` ORDER BY `$pk`")->fetchAll(PDO::FETCH_ASSOC);
                        $headers=$data?array_keys($data[0]):array_column($pdo->query("SHOW COLUMNS FROM `$tbl`")->fetchAll(PDO::FETCH_ASSOC),'Field');
                        $rows=array_map(fn($r)=>array_map(fn($v)=>$v===null?'':(string)$v,array_values($r)),$data);
                        $sheets[$tbl]=['headers'=>$headers,'rows'=>$rows];
                    }catch(Exception $e){ /* table missing on this install: skip */ }
                }
                $file="website_backup_$stamp";
            }
            send_workbook($file,$sheets);
        }catch(Throwable $e){
            error_log('Website export error: '.$e->getMessage());
            http_response_code(500); die('Could not create the Excel file.');
        }
    }
    // AJAX Handlers
    if(isset($_POST['ajax'])){
        header('Content-Type: application/json');
        if($_POST['action']==='toggle_section'){
            try{
                $allowedColumns=['ShowAboutSection','ShowStatsSection','ShowProgramsSection','ShowGallerySection','ShowPartnersSection'];
                if(!in_array($_POST['section'],$allowedColumns)){
                    echo json_encode(['success'=>false,'error'=>'Invalid section']);
                    exit;
                }
                $column=$_POST['section'];
                $stmt=$pdo->prepare("UPDATE ThemeSettings SET `{$column}`=? WHERE SettingID=1");
                $stmt->execute([$_POST['value']?1:0]);
                echo json_encode(['success'=>true]);
            }catch(Exception $e){echo json_encode(['success'=>false]);}
            exit;
        }
        if($_POST['action']==='save_section_order'){
            try{
                $sections=json_decode($_POST['sections'],true);
                if(!$sections){
                    echo json_encode(['success'=>false,'error'=>'Invalid JSON']);
                    exit;
                }
                foreach($sections as $section){
                    $stmt=$pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES(?,?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)");
                    $result=$stmt->execute(['SectionOrder_'.$section['section'],$section['order']]);
                    if(!$result){
                        echo json_encode(['success'=>false,'error'=>'Database error']);
                        exit;
                    }
                }
                echo json_encode(['success'=>true,'message'=>'Order saved']);
            }catch(Exception $e){
                echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
            }
            exit;
        }
        if($_POST['action']==='update_opacity'){
            try{
                $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('ImageOpacity',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$_POST['opacity']]);
                echo json_encode(['success'=>true]);
            }catch(Exception $e){echo json_encode(['success'=>false]);}
            exit;
        }
        if($_POST['action']==='update_theme'){
            try{
                $pdo->prepare("UPDATE ThemeSettings SET BackgroundColor=?,HeroTitle=?,HeroSubtitle=? WHERE SettingID=1")->execute([$_POST['bg_color'],$_POST['hero_title'],$_POST['hero_subtitle']]);
                echo json_encode(['success'=>true]);
            }catch(Exception $e){echo json_encode(['success'=>false]);}
            exit;
        }
        if($_POST['action']==='delete_item'){
            try{
                $table=$_POST['table'];
                $id=(int)$_POST['id'];
                $allowedTables=['MediaGallery','Gallery','Testimonials','Campaigns','Events','Partners','TeamMembers','Benefits'];
                if(!in_array($table,$allowedTables)){
                    echo json_encode(['success'=>false,'error'=>'Invalid table']);
                    exit;
                }
                $idColumns=[
                    'MediaGallery'=>'MediaID',
                    'Gallery'=>'GalleryID',
                    'Testimonials'=>'TestimonialID',
                    'Campaigns'=>'CampaignID',
                    'Events'=>'EventID',
                    'Partners'=>'PartnerID',
                    'TeamMembers'=>'TeamID',
                    'Benefits'=>'BenefitID'
                ];
                $idColumn=$idColumns[$table];
                $stmt=$pdo->prepare("UPDATE `{$table}` SET IsActive=0 WHERE `{$idColumn}`=?");
                $stmt->execute([$id]);
                echo json_encode(['success'=>true,'message'=>'Deleted successfully']);
            }catch(Exception $e){
                echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
            }
            exit;
        }
    }
    
    try{
        $stats=$pdo->query("SELECT 
            (SELECT COUNT(*) FROM Donations WHERE IsActive=1) as total_donations,
            (SELECT IFNULL(SUM(Amount),0) FROM Donations WHERE Status='Approved' AND IsActive=1) as total_amount,
            (SELECT COUNT(*) FROM Donations WHERE Status='Pending' AND IsActive=1) as pending,
            (SELECT COUNT(*) FROM Partners WHERE IsActive=1) as partners,
            (SELECT COUNT(*) FROM MediaGallery WHERE IsActive=1) as media_items,
            (SELECT COUNT(*) FROM Testimonials WHERE IsActive=1) as testimonials,
            (SELECT COUNT(*) FROM Campaigns WHERE IsActive=1) as campaigns,
            (SELECT COUNT(*) FROM Events WHERE IsActive=1) as events")->fetch(PDO::FETCH_ASSOC);
    }catch(Exception $e){
        $stats=['total_donations'=>0,'total_amount'=>0,'pending'=>0,'partners'=>0,'media_items'=>0,'testimonials'=>0,'campaigns'=>0,'events'=>0];
    }
    
    if(isset($_POST['approve'])){
        try{
            $stmt=$pdo->prepare("SELECT * FROM Donations WHERE DonationID=?");
            $stmt->execute([(int)$_POST['id']]);
            $donation=$stmt->fetch(PDO::FETCH_ASSOC);
            // only credit the campaign the FIRST time it becomes Approved (clicking twice must not double-count)
            if($donation && $donation['Status']!=='Approved'){
                $pdo->prepare("UPDATE Donations SET Status='Approved' WHERE DonationID=?")->execute([(int)$_POST['id']]);
                if($donation['CampaignID']){
                    $pdo->prepare("UPDATE Campaigns SET CurrentAmount=CurrentAmount+? WHERE CampaignID=?")->execute([$donation['Amount'],$donation['CampaignID']]);
                }
            }
            header('Location: admin.php?tab=donations'.(!empty($_POST['back'])?'&'.preg_replace('/[^A-Za-z0-9=&%_.+-]/','',$_POST['back']):''));
            exit;
        }catch(Exception $e){
            error_log("Donation approval error: " . $e->getMessage());
        }
    }
    if(isset($_POST['reject'])){
        try{
            $stmt=$pdo->prepare("SELECT * FROM Donations WHERE DonationID=?");
            $stmt->execute([(int)$_POST['id']]);
            $donation=$stmt->fetch(PDO::FETCH_ASSOC);
            if($donation && $donation['Status']!=='Rejected'){
                // rejecting something that was already counted takes it back out of the campaign total
                if($donation['Status']==='Approved' && $donation['CampaignID']){
                    $pdo->prepare("UPDATE Campaigns SET CurrentAmount=GREATEST(0,CurrentAmount-?) WHERE CampaignID=?")->execute([$donation['Amount'],$donation['CampaignID']]);
                }
                $pdo->prepare("UPDATE Donations SET Status='Rejected' WHERE DonationID=?")->execute([(int)$_POST['id']]);
            }
            header('Location: admin.php?tab=donations'.(!empty($_POST['back'])?'&'.preg_replace('/[^A-Za-z0-9=&%_.+-]/','',$_POST['back']):''));
            exit;
        }catch(Exception $e){
            error_log("Donation rejection error: " . $e->getMessage());
        }
    }
    
    // Upload Handlers with security validation
    if(isset($_POST['upload_media']) && isset($_FILES['media_file'])){
        $file=$_FILES['media_file'];
        if($file['error']===0 && $file['size']<=10*1024*1024){
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            $type=in_array($ext,['mp4','webm','mov'])?'video':'image';
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$file['tmp_name']);
            finfo_close($finfo);
            $allowedMimes=['image/jpeg','image/png','image/gif','video/mp4','video/webm','video/quicktime'];
            if(in_array($ext,['jpg','jpeg','png','gif','mp4','webm','mov']) && in_array($mimeType,$allowedMimes)){
                $name='media_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $path=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($path)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(!is_dir('uploads'))mkdir('uploads',0755,true);
                if(move_uploaded_file($file['tmp_name'],$path)){
                    $relativePath='uploads/'.$name;
                    $pdo->prepare("INSERT INTO MediaGallery(Title,FilePath,FileType,Category,Description,DisplayOrder)VALUES(?,?,?,?,?,?)")->execute([$_POST['title'],$relativePath,$type,$_POST['category'],$_POST['description'],$_POST['display_order']??0]);
                    header('Location: admin.php?tab=media');
                    exit;
                }
            }
        }
    }
    if(isset($_POST['add_testimonial'])){
        $photo='';
        if(isset($_FILES['photo']) && $_FILES['photo']['error']===0 && $_FILES['photo']['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                $name='testimonial_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $fullPath=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($fullPath)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($_FILES['photo']['tmp_name'],$fullPath)){
                    $photo='uploads/'.$name;
                }
            }
        }
        $pdo->prepare("INSERT INTO Testimonials(Name,Message,Photo,Position,DisplayOrder)VALUES(?,?,?,?,?)")->execute([$_POST['name'],$_POST['message'],$photo,$_POST['position'],$_POST['display_order']??0]);
        header('Location: admin.php?tab=testimonials');
        exit;
    }
    if(isset($_POST['add_campaign'])){
        $img='';
        if(isset($_FILES['image']) && $_FILES['image']['error']===0 && $_FILES['image']['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                $name='campaign_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $fullPath=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($fullPath)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($_FILES['image']['tmp_name'],$fullPath)){
                    $img='uploads/'.$name;
                }
            }
        }
        $pdo->prepare("INSERT INTO Campaigns(Title,Description,TargetAmount,ImagePath,IsFeatured)VALUES(?,?,?,?,?)")->execute([$_POST['title'],$_POST['description'],$_POST['target_amount'],$img,isset($_POST['is_featured'])?1:0]);
        header('Location: admin.php?tab=campaigns');
        exit;
    }
    if(isset($_POST['add_event'])){
        $pdo->prepare("INSERT INTO Events(Title,Description,EventDate,Location,ShowPopup)VALUES(?,?,?,?,?)")->execute([$_POST['title'],$_POST['description'],$_POST['event_date'],$_POST['location'],isset($_POST['show_popup'])?1:0]);
        header('Location: admin.php?tab=events');
        exit;
    }
    if(isset($_POST['add_partner'])){
        $logo='';
        if(isset($_FILES['logo']) && $_FILES['logo']['error']===0 && $_FILES['logo']['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$_FILES['logo']['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                $name='partner_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $fullPath=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($fullPath)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($_FILES['logo']['tmp_name'],$fullPath)){
                    $logo='uploads/'.$name;
                }
            }
        }
        $pdo->prepare("INSERT INTO Partners(PartnerName,WhatsAppNumber,ContactInfo,LogoPath,DisplayOrder)VALUES(?,?,?,?,?)")->execute([$_POST['name'],$_POST['mobile'],$_POST['address'],$logo,$_POST['display_order']??0]);
        header('Location: admin.php?tab=partners');
        exit;
    }
    if(isset($_POST['upload_gallery']) && isset($_FILES['gallery_image'])){
        $file=$_FILES['gallery_image'];
        if($file['error']===0 && $file['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$file['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','gif']) && in_array($mimeType,['image/jpeg','image/png','image/gif'])){
                $name='gallery_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $path=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($path)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($file['tmp_name'],$path)){
                    $relativePath='uploads/'.$name;
                    $pdo->prepare("INSERT INTO Gallery(ImagePath,Title,Description,Category)VALUES(?,?,?,?)")->execute([$relativePath,$_POST['title'],$_POST['description'],$_POST['category']]);
                    header('Location: admin.php?tab=gallery');
                    exit;
                }
            }
        }
    }
    if(isset($_POST['update_contact'])){
        $pdo->prepare("UPDATE ContactInfo SET Phone=?,Email=?,Address=?,FacebookURL=?,TwitterURL=?,InstagramURL=?,LinkedInURL=?,WhatsAppNumber=? WHERE ContactID=1")->execute([$_POST['phone'],$_POST['email'],$_POST['address'],$_POST['facebook'],$_POST['twitter'],$_POST['instagram'],$_POST['linkedin'],$_POST['whatsapp']]);
        header('Location: admin.php?tab=contact');
        exit;
    }
    if(isset($_POST['update_about'])){
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('AboutSubtitle',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['about_subtitle'],$_POST['about_subtitle']]);
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('MissionText',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['mission_text'],$_POST['mission_text']]);
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('VisionText',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['vision_text'],$_POST['vision_text']]);
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('LivesImpacted',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['lives_impacted'],$_POST['lives_impacted']]);
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('ProjectsCompleted',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['projects_completed'],$_POST['projects_completed']]);
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('CommunitiesServed',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['communities_served'],$_POST['communities_served']]);
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('YearsOfService',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['years_of_service'],$_POST['years_of_service']]);
        header('Location: admin.php?tab=about');
        exit;
    }
    if(isset($_POST['update_upi'])){
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('UpiId',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['upi_id'],$_POST['upi_id']]);
        header('Location: admin.php?tab=settings');
        exit;
    }
    if(isset($_POST['upload_qr']) && isset($_FILES['qr_code'])){
        $file=$_FILES['qr_code'];
        if($file['error']===0 && $file['size']<=2*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$file['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            $sanitizedExt=basename($ext);
            if(in_array($sanitizedExt,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                try{
                    $currentQR=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='QRCodePath'")->fetchColumn();
                    if($currentQR && file_exists($currentQR))unlink($currentQR);
                    $uploadDir=realpath('uploads');
                    if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                    $path=$uploadDir.DIRECTORY_SEPARATOR.'qr-code.png';
                    if(strpos(realpath(dirname($path)),$uploadDir)!==0){throw new Exception('Invalid path');}
                    if(move_uploaded_file($file['tmp_name'],$path)){
                        $relativePath='uploads/qr-code.png';
                        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('QRCodePath',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$relativePath,$relativePath]);
                    }
                }catch(Exception $e){
                    error_log("QR upload error: " . $e->getMessage());
                }
            }
            header('Location: admin.php?tab=settings');
            exit;
        }
    }
    if(isset($_POST['upload_logo']) && isset($_FILES['logo'])){
        $file=$_FILES['logo'];
        if($file['error']===0 && $file['size']<=2*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$file['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','gif']) && in_array($mimeType,['image/jpeg','image/png','image/gif'])){
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $path=$uploadDir.DIRECTORY_SEPARATOR.'logo.'.$ext;
                if(strpos(realpath(dirname($path)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($file['tmp_name'],$path)){
                    $relativePath='uploads/logo.'.$ext;
                    $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('LogoPath',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$relativePath,$relativePath]);
                    header('Location: admin.php?tab=theme');
                    exit;
                }
            }
        }
    }
    if(isset($_POST['upload_bg']) && isset($_FILES['background'])){
        $file=$_FILES['background'];
        if($file['error']===0 && $file['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$file['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $path=$uploadDir.DIRECTORY_SEPARATOR.'background.'.$ext;
                if(strpos(realpath(dirname($path)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($file['tmp_name'],$path)){
                    $relativePath='uploads/background.'.$ext;
                    $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('BackgroundPath',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$relativePath,$relativePath]);
                    if(isset($_POST['img_opacity'])){
                        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('ImageOpacity',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['img_opacity'],$_POST['img_opacity']]);
                    }
                    header('Location: admin.php?tab=theme');
                    exit;
                }
            }
        }
    }
    if(isset($_POST['update_heading'])){
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('SiteHeading',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$_POST['heading']]);
        if(isset($_POST['font_color'])){
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('FontColor',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$_POST['font_color']]);
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['upload_hero_video']) && isset($_FILES['hero_video'])){
        $file=$_FILES['hero_video'];
        if($file['error']===0 && $file['size']<=20*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$file['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['mp4','webm','mov']) && in_array($mimeType,['video/mp4','video/webm','video/quicktime'])){
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $path=$uploadDir.DIRECTORY_SEPARATOR.'hero-video.'.$ext;
                if(strpos(realpath(dirname($path)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($file['tmp_name'],$path)){
                    $relativePath='uploads/hero-video.'.$ext;
                    $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('HeroVideoPath',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$relativePath,$relativePath]);
                    header('Location: admin.php?tab=theme');
                    exit;
                }
            }
        }
    }
    if(isset($_POST['update_theme_color'])){
        $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('ThemeColor',?)ON DUPLICATE KEY UPDATE SettingValue=?")->execute([$_POST['theme_color'],$_POST['theme_color']]);
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['update_custom_theme'])){
        try{
            $primary=$_POST['primary_color'];
            $secondary=$_POST['secondary_color'];
            $gradientType=$_POST['gradient_type'];
            $direction=$_POST['gradient_direction'];
            $heroFontColor=$_POST['hero_font_color']??'#ffffff';
            $imgOpacity=$_POST['img_opacity']??'0.3';
            
            // Update both tables for compatibility
            $pdo->prepare("UPDATE ThemeSettings SET BackgroundColor=? WHERE SettingID=1")->execute([$primary]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('PrimaryColor',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$primary]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('SecondaryColor',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$secondary]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('GradientType',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$gradientType]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('GradientDirection',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$direction]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('HeroFontColor',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$heroFontColor]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('ImageOpacity',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$imgOpacity]);
            
            $_SESSION['theme_success']='Theme updated successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error updating theme: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['update_section_order'])){
        try{
            $newOrder = [
                'about' => (int)$_POST['about_order'],
                'stats' => (int)$_POST['stats_order'],
                'programs' => (int)$_POST['programs_order'],
                'gallery' => (int)$_POST['gallery_order'],
                'partners' => (int)$_POST['partners_order']
            ];
            
            // Handle duplicate orders by shifting others
            $finalOrder = [];
            foreach($newOrder as $section => $order) {
                // If order already exists, shift existing ones
                foreach($finalOrder as $existingSection => $existingOrder) {
                    if($existingOrder >= $order) {
                        $finalOrder[$existingSection]++;
                    }
                }
                $finalOrder[$section] = $order;
            }
            
            // Save to database
            foreach($finalOrder as $section => $order) {
                $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES(?,?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute(["SectionOrder_$section", $order]);
            }
            
            $_SESSION['theme_success']='Section order updated successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error updating section order: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['clear_section_order'])){
        try{
            $pdo->prepare("DELETE FROM AdminSettings WHERE SettingKey LIKE 'SectionOrder_%'")->execute();
            $_SESSION['theme_success']='Section order cleared successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error clearing section order: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['update_theme'])){
        try{
            $pdo->prepare("UPDATE ThemeSettings SET BackgroundColor=?,HeroTitle=?,HeroSubtitle=? WHERE SettingID=1")->execute([$_POST['bg_color'],$_POST['hero_title'],$_POST['hero_subtitle']]);
            $pdo->prepare("INSERT INTO AdminSettings(SettingKey,SettingValue)VALUES('PrimaryColor',?)ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)")->execute([$_POST['bg_color']]);
            $_SESSION['theme_success']='Theme updated successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error updating theme: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['edit_partner'])){
        $logo=$_POST['existing_logo']??'';
        if(isset($_FILES['logo']) && $_FILES['logo']['error']===0 && $_FILES['logo']['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$_FILES['logo']['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                if($logo && file_exists($logo)){
                    unlink($logo);
                }
                $name='partner_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $fullPath=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($fullPath)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($_FILES['logo']['tmp_name'],$fullPath)){
                    $logo='uploads/'.$name;
                }
            }
        }
        $pdo->prepare("UPDATE Partners SET PartnerName=?,WhatsAppNumber=?,ContactInfo=?,LogoPath=? WHERE PartnerID=?")->execute([$_POST['name'],$_POST['mobile'],$_POST['address'],$logo,$_POST['id']]);
        header('Location: admin.php?tab=partners');
        exit;
    }
    if(isset($_POST['edit_campaign'])){
        $img=$_POST['existing_image'];
        if(isset($_FILES['image']) && $_FILES['image']['error']===0 && $_FILES['image']['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                $name='campaign_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $fullPath=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($fullPath)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($_FILES['image']['tmp_name'],$fullPath)){
                    $img='uploads/'.$name;
                }
            }
        }
        $pdo->prepare("UPDATE Campaigns SET Title=?,Description=?,TargetAmount=?,ImagePath=?,IsFeatured=? WHERE CampaignID=?")->execute([$_POST['title'],$_POST['description'],$_POST['target_amount'],$img,isset($_POST['is_featured'])?1:0,$_POST['id']]);
        header('Location: admin.php?tab=campaigns');
        exit;
    }
    if(isset($_POST['edit_event'])){
        $pdo->prepare("UPDATE Events SET Title=?,Description=?,EventDate=?,Location=?,ShowPopup=? WHERE EventID=?")->execute([$_POST['title'],$_POST['description'],$_POST['event_date'],$_POST['location'],isset($_POST['show_popup'])?1:0,$_POST['id']]);
        header('Location: admin.php?tab=events');
        exit;
    }
    if(isset($_POST['edit_testimonial'])){
        $photo=$_POST['existing_photo'];
        if(isset($_FILES['photo']) && $_FILES['photo']['error']===0 && $_FILES['photo']['size']<=5*1024*1024){
            $finfo=finfo_open(FILEINFO_MIME_TYPE);
            $mimeType=finfo_file($finfo,$_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png']) && in_array($mimeType,['image/jpeg','image/png'])){
                $name='testimonial_'.bin2hex(random_bytes(8)).'.'.$ext;
                $uploadDir=realpath('uploads');
                if(!$uploadDir){mkdir('uploads',0755,true);$uploadDir=realpath('uploads');}
                $fullPath=$uploadDir.DIRECTORY_SEPARATOR.$name;
                if(strpos(realpath(dirname($fullPath)),$uploadDir)!==0){throw new Exception('Invalid path');}
                if(move_uploaded_file($_FILES['photo']['tmp_name'],$fullPath)){
                    $photo='uploads/'.$name;
                }
            }
        }
        $pdo->prepare("UPDATE Testimonials SET Name=?,Message=?,Photo=?,Position=? WHERE TestimonialID=?")->execute([$_POST['name'],$_POST['message'],$photo,$_POST['position'],$_POST['id']]);
        header('Location: admin.php?tab=testimonials');
        exit;
    }
    if(isset($_POST['delete_logo'])){
        try{
            $logoPath=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='LogoPath'")->fetchColumn();
            if($logoPath && file_exists($logoPath)) {
                unlink($logoPath);
            }
            $pdo->prepare("UPDATE AdminSettings SET SettingValue='' WHERE SettingKey='LogoPath'")->execute();
            $_SESSION['theme_success']='Logo deleted successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error deleting logo: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['delete_background'])){
        try{
            $bgPath=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='BackgroundPath'")->fetchColumn();
            if($bgPath && file_exists($bgPath)) {
                unlink($bgPath);
            }
            $pdo->prepare("UPDATE AdminSettings SET SettingValue='' WHERE SettingKey='BackgroundPath'")->execute();
            $_SESSION['theme_success']='Background image deleted successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error deleting background: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    if(isset($_POST['delete_hero_video'])){
        try{
            $heroVideo=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='HeroVideoPath'")->fetchColumn();
            if($heroVideo && file_exists($heroVideo)) {
                unlink($heroVideo);
            }
            $pdo->prepare("UPDATE AdminSettings SET SettingValue='' WHERE SettingKey='HeroVideoPath'")->execute();
            $_SESSION['theme_success']='Hero video deleted successfully!';
        }catch(Exception $e){
            $_SESSION['theme_error']='Error deleting video: '.$e->getMessage();
        }
        header('Location: admin.php?tab=theme');
        exit;
    }
    
    try{
        // Donations: server-side search/status filter + pagination
        [$dwhere,$dparams]=donation_filter($_GET);
        [$dpage,$dper]=page_params(25);
        $dc=$pdo->prepare("SELECT COUNT(*) FROM Donations d $dwhere"); $dc->execute($dparams);
        $donationTotal=(int)$dc->fetchColumn();
        $dpage=min($dpage,max(1,(int)ceil($donationTotal/$dper)));
        $ds=$pdo->prepare("SELECT d.*, c.Title AS CampaignTitle FROM Donations d LEFT JOIN Campaigns c ON c.CampaignID=d.CampaignID $dwhere ORDER BY d.DonationDate DESC, d.DonationID DESC LIMIT ".(int)$dper." OFFSET ".(int)(($dpage-1)*$dper));
        $ds->execute($dparams);
        $donations=$ds->fetchAll(PDO::FETCH_ASSOC);
        $gallery=$pdo->query("SELECT * FROM Gallery WHERE IsActive=1 ORDER BY UploadDate DESC")->fetchAll(PDO::FETCH_ASSOC);
        $mediaGallery=$pdo->query("SELECT * FROM MediaGallery WHERE IsActive=1 ORDER BY DisplayOrder,UploadDate DESC")->fetchAll(PDO::FETCH_ASSOC);
        $partners=$pdo->query("SELECT * FROM Partners WHERE IsActive=1 ORDER BY DisplayOrder,CreatedDate DESC")->fetchAll(PDO::FETCH_ASSOC);
        $testimonials=$pdo->query("SELECT * FROM Testimonials WHERE IsActive=1 ORDER BY DisplayOrder,CreatedDate DESC")->fetchAll(PDO::FETCH_ASSOC);
        $campaigns=$pdo->query("SELECT * FROM Campaigns WHERE IsActive=1 ORDER BY CreatedDate DESC")->fetchAll(PDO::FETCH_ASSOC);
        $events=$pdo->query("SELECT * FROM Events WHERE IsActive=1 ORDER BY EventDate DESC")->fetchAll(PDO::FETCH_ASSOC);
        $contact=$pdo->query("SELECT * FROM ContactInfo ORDER BY ContactID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $qrCode=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='QRCodePath'")->fetchColumn();
        $aboutSubtitle=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='AboutSubtitle'")->fetchColumn();
        $missionText=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='MissionText'")->fetchColumn();
        $visionText=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='VisionText'")->fetchColumn();
        $livesImpacted=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='LivesImpacted'")->fetchColumn();
        $projectsCompleted=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='ProjectsCompleted'")->fetchColumn();
        $communitiesServed=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='CommunitiesServed'")->fetchColumn();
        $yearsOfService=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='YearsOfService'")->fetchColumn();
        $upiId=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='UpiId'")->fetchColumn();
        $logoPath=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='LogoPath'")->fetchColumn();
        $bgPath=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='BackgroundPath'")->fetchColumn();
        $siteHeading=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SiteHeading'")->fetchColumn();
        $heroVideo=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='HeroVideoPath'")->fetchColumn();
        $themeColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='ThemeColor'")->fetchColumn();
        $secondaryColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SecondaryColor'")->fetchColumn();
        $gradientType=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='GradientType'")->fetchColumn();
        $gradientDirection=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='GradientDirection'")->fetchColumn();
        $fontColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='FontColor'")->fetchColumn();
        $primaryColorAdmin=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='PrimaryColor'")->fetchColumn();
        $heroFontColor=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='HeroFontColor'")->fetchColumn()??'#ffffff';
        $imgOpacity=$pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='ImageOpacity'")->fetchColumn()??'0.3';
        $themeSettings=$pdo->query("SELECT * FROM ThemeSettings ORDER BY SettingID DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        // Use AdminSettings primary color if available, otherwise fallback to ThemeSettings
        if($primaryColorAdmin) $themeSettings['BackgroundColor'] = $primaryColorAdmin;
    }catch(Exception $e){
        $donations=[];$donationTotal=0;$dpage=1;$dper=25;$gallery=[];$mediaGallery=[];$partners=[];$testimonials=[];$campaigns=[];$events=[];$contact=null;$qrCode='';$logoPath='';$bgPath='';$siteHeading='Relief Wave Foundation';$heroVideo='';$themeColor='gray';$secondaryColor='#495057';$gradientType='linear';$gradientDirection='135deg';$fontColor='#333333';$primaryColorAdmin='#667eea';$heroFontColor='#ffffff';$bgOpacity='0.3';
        $themeSettings=['BackgroundColor'=>'#667eea','HeroTitle'=>'Together for Hope, Together for Change','HeroSubtitle'=>'Empowering communities across India','ShowAboutSection'=>1,'ShowStatsSection'=>1,'ShowProgramsSection'=>1,'ShowGallerySection'=>1,'ShowPartnersSection'=>1];
        $upiId='reliefwave@paytm';
    }
}
$currentTab=$_GET['tab']??'overview';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f7;color:#333;line-height:1.6}.login-container{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6c757d 0%,#495057 100%)}.login-box{background:#fff;padding:40px;border-radius:20px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3)}.login-box h2{text-align:center;color:#6c757d;margin-bottom:30px;font-size:2rem}.form-group{margin:20px 0}.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#333}.form-group input,.form-group select,.form-group textarea{width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;font-size:1rem;transition:border-color 0.3s}.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#6c757d;box-shadow:0 0 0 3px rgba(108,117,125,0.1)}.btn{width:100%;padding:15px;background:#6c757d;color:#fff;border:none;border-radius:8px;font-size:1.1rem;cursor:pointer;transition:all 0.3s;font-weight:600}.btn:hover{background:#5a6268;transform:translateY(-2px)}.btn-small{width:auto;padding:8px 20px;font-size:0.9rem;display:inline-block;margin:2px}.btn-danger{background:#dc3545}.btn-danger:hover{background:#c82333}.btn-success{background:#28a745}.btn-success:hover{background:#218838}.header{background:#fff;padding:20px;box-shadow:0 2px 20px rgba(0,0,0,0.1);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;position:sticky;top:0;z-index:100}.header h1{color:#6c757d;font-size:1.8rem}.logout-btn{padding:10px 25px;background:#dc3545;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:all 0.3s}.logout-btn:hover{background:#c82333;transform:translateY(-2px)}.container{max-width:1400px;margin:0 auto;padding:30px 20px}.tabs{display:flex;gap:5px;margin-bottom:30px;flex-wrap:wrap;background:#fff;padding:10px;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.1)}.tab-btn{padding:12px 20px;background:transparent;border:none;color:#6c757d;border-radius:8px;cursor:pointer;transition:all 0.3s;font-weight:500}.tab-btn.active{background:#6c757d;color:#fff;box-shadow:0 5px 15px rgba(108,117,125,0.3)}.tab-btn:hover:not(.active){background:rgba(108,117,125,0.1)}.tab-content{display:none;background:#fff;padding:30px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.1)}.tab-content.active{display:block;animation:fadeIn 0.3s}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}.stat-card{background:linear-gradient(135deg,#6c757d 0%,#495057 100%);color:#fff;padding:30px;border-radius:15px;text-align:center;cursor:pointer;transition:transform 0.3s}.stat-card:hover{transform:translateY(-5px)}.stat-number{font-size:3rem;font-weight:bold;margin-bottom:10px}table{width:100%;border-collapse:collapse;margin:20px 0;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.1)}table th{background:#6c757d;color:#fff;padding:15px;text-align:left;font-weight:600}table td{padding:12px 15px;border-bottom:1px solid #f0f0f0}table tr:hover{background:#f8f9fa}.upload-section{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);padding:25px;border-radius:15px;margin-bottom:30px;border:2px dashed #6c757d}.upload-section h3{color:#6c757d;margin-bottom:20px}.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin:20px 0}.media-item{position:relative;border-radius:10px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.1);transition:transform 0.3s}.media-item:hover{transform:scale(1.05)}.media-item img,.media-item video{width:100%;height:150px;object-fit:cover}.media-overlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,0.8));color:#fff;padding:15px;font-size:0.9rem}.toggle-switch{position:relative;display:inline-block;width:60px;height:34px}.toggle-switch input{opacity:0;width:0;height:0}.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#ccc;transition:0.4s;border-radius:34px}.slider:before{position:absolute;content:"";height:26px;width:26px;left:4px;bottom:4px;background:#fff;transition:0.4s;border-radius:50%}input:checked+.slider{background:#6c757d}input:checked+.slider:before{transform:translateX(26px)}.preview-card{background:#fff;border-radius:10px;padding:20px;margin:10px 0;box-shadow:0 5px 15px rgba(0,0,0,0.1);transition:transform 0.3s}.preview-card:hover{transform:translateY(-3px)}.color-picker{width:50px;height:40px;border:none;border-radius:8px;cursor:pointer}.alert{padding:15px;border-radius:8px;margin:15px 0;border-left:4px solid}.alert-info{background:#d1ecf1;color:#0c5460;border-color:#17a2b8}@keyframes fadeIn{from{opacity:0}to{opacity:1}}@media(max-width:768px){.header{flex-direction:column;gap:10px}.tabs{flex-direction:column}.tab-btn{justify-content:center}.stats-grid{grid-template-columns:repeat(2,1fr)}.form-row{grid-template-columns:1fr}table{font-size:0.9rem}.container{padding:20px 10px}}

/* ===== UI refresh (appended; overrides the rules above) ===== */
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f1f5f9;color:#0f172a;-webkit-text-size-adjust:100%}
.login-container{background:radial-gradient(circle at 15% 20%,rgba(20,184,166,.55),transparent 42%),linear-gradient(135deg,#0f172a,#1e293b)}
.login-box{border-radius:24px;padding:40px 34px}
.login-box h2{color:#0f172a}
.form-group input,.form-group select,.form-group textarea{border:1.5px solid #cbd5e1;border-radius:12px;padding:12px 14px;font-family:inherit;background:#fff}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#0f766e;box-shadow:0 0 0 4px rgba(15,118,110,.14)}
.btn{background:#0f766e;border-radius:12px;font-family:inherit;font-weight:700}
.btn:hover{background:#115e59}
.btn-danger{background:#dc2626}.btn-danger:hover{background:#b91c1c}
.btn-success{background:#16a34a}.btn-success:hover{background:#15803d}
.header{padding:14px 24px;box-shadow:none;border-bottom:1px solid #e2e8f0;gap:10px}
.header h1{font-size:1.2rem;color:#0f172a}
.logout-btn{background:#dc2626;border-radius:10px;font-weight:600}
.container{padding:24px 20px 60px}
.tabs{position:sticky;top:67px;z-index:90;flex-wrap:nowrap;overflow-x:auto;gap:4px;padding:8px;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 10px 24px -16px rgba(15,23,42,.3);scrollbar-width:none;-webkit-overflow-scrolling:touch}
.tabs::-webkit-scrollbar{display:none}
.tab-btn{white-space:nowrap;flex-shrink:0;border-radius:12px;font-weight:600;font-size:.92rem;color:#475569;font-family:inherit}
.tab-btn.active{background:#0f766e;box-shadow:0 10px 20px -10px rgba(15,118,110,.7)}
.tab-btn:hover:not(.active){background:#f1f5f9}
.tab-content{border-radius:20px;border:1px solid #e2e8f0;box-shadow:0 14px 34px -22px rgba(15,23,42,.35)}
.tab-content h2{font-size:1.4rem;margin-bottom:18px}
.stat-card{background:linear-gradient(135deg,#0f766e,#0e7490);border-radius:18px;box-shadow:0 14px 28px -16px rgba(15,118,110,.7)}
.stat-number{font-size:2.3rem}
table{border-radius:14px;box-shadow:none;border:1px solid #e2e8f0}
table th{background:#0f172a;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em}
table td{vertical-align:middle}
.upload-section{background:#f8fafc;border:2px dashed #94a3b8;border-radius:18px}
.upload-section h3{color:#0f172a}
.media-item,.preview-card{border-radius:16px}
.toggle-switch input:checked+.slider,input:checked+.slider{background:#0f766e}
.dfilter{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:4px 0 8px}
.dfilter input[type=search],.dfilter select{padding:10px 12px;border:1.5px solid #cbd5e1;border-radius:10px;font:inherit;background:#fff}
.dfilter input[type=search]{min-width:220px}
.dfilter .btn-small{border-radius:10px}
.dfilter .dexp{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.dbadge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:.78rem;font-weight:700}
.d-pending{background:#fef3c7;color:#92400e}.d-approved{background:#dcfce7;color:#166534}.d-rejected{background:#fee2e2;color:#991b1b}
.pager{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;font-size:13px;color:#64748b}
.pager-links{display:flex;gap:4px;flex-wrap:wrap;align-items:center}
.pager-links a,.pager-links .cur,.pager-links .off,.pager-links .dots{min-width:36px;height:36px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;text-decoration:none;font-weight:600;font-size:13px}
.pager-links a{background:#fff;border:1px solid #e2e8f0;color:#0f172a}.pager-links a:hover{border-color:#0f766e;color:#0f766e}
.pager-links .cur{background:#0f766e;color:#fff}.pager-links .off{color:#cbd5e1;border:1px solid #f1f5f9}
.pager-per select{padding:6px 10px;border:1px solid #cbd5e1;border-radius:8px;font:inherit}
@media(max-width:768px){.dfilter input[type=search]{min-width:0;width:100%}.dfilter .dexp{margin-left:0;width:100%}}
@media(max-width:768px){
 .header{flex-direction:row;flex-wrap:wrap;padding:12px 14px}
 .header h1{font-size:1.05rem}
 .header>div{display:flex;align-items:center;gap:8px;font-size:.82rem}
 .header>div form{margin-left:0!important}
 .container{padding:14px 10px 50px}
 .tabs{flex-direction:row;position:static;margin-bottom:16px}
 .tab-btn{padding:10px 14px}
 .tab-content{padding:18px 14px;border-radius:16px}
 .stats-grid{grid-template-columns:1fr 1fr;gap:12px}
 .stat-card{padding:18px 12px}
 .stat-number{font-size:1.6rem}
 table{display:block;overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch}
 table th,table td{padding:10px 12px;font-size:.85rem}
 .form-group input,.form-group select,.form-group textarea{font-size:16px}
 .btn-small{padding:9px 14px}
}
</style>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php if(!isset($_SESSION['admin'])): ?>
<div class="login-container">
<div class="login-box">
<h2>🌊 Admin Login</h2>
<form method="POST">
<div class="form-group">
<label>Email</label>
<input type="email" name="email" required>
</div>
<div class="form-group">
<label>Password</label>
<input type="password" name="password" required>
</div>
<button type="submit" name="login" class="btn">Login</button>
<?php if(isset($error)): ?>
<p style="color:red;text-align:center;margin-top:15px"><?= $error ?></p>
<?php endif; ?>
</form>
</div>
</div>
<?php else: ?>
<div class="header">
<h1>🌊 Relief Wave Admin</h1>
<div>
<span>Welcome, <?= htmlspecialchars($_SESSION['admin']) ?></span>
<form method="POST" style="display:inline;margin-left:15px">
<button type="submit" name="logout" class="logout-btn">Logout</button>
</form>
</div>
</div>

<div class="container">
<div class="tabs">
<button class="tab-btn <?= $currentTab==='overview'?'active':'' ?>" onclick="showTab('overview')">📊 Overview</button>
<button class="tab-btn <?= $currentTab==='donations'?'active':'' ?>" onclick="showTab('donations')">💰 Donations</button>
<button class="tab-btn <?= $currentTab==='media'?'active':'' ?>" onclick="showTab('media')">🎬 Media</button>
<button class="tab-btn <?= $currentTab==='gallery'?'active':'' ?>" onclick="showTab('gallery')">📷 Gallery</button>
<button class="tab-btn <?= $currentTab==='testimonials'?'active':'' ?>" onclick="showTab('testimonials')">💬 Testimonials</button>
<button class="tab-btn <?= $currentTab==='campaigns'?'active':'' ?>" onclick="showTab('campaigns')">🎯 Campaigns</button>
<button class="tab-btn <?= $currentTab==='events'?'active':'' ?>" onclick="showTab('events')">📅 Events</button>
<button class="tab-btn <?= $currentTab==='partners'?'active':'' ?>" onclick="showTab('partners')">🤝 Partners</button>
<button class="tab-btn <?= $currentTab==='about'?'active':'' ?>" onclick="showTab('about')">📖 About</button>
<button class="tab-btn <?= $currentTab==='theme'?'active':'' ?>" onclick="showTab('theme')">🎨 Theme</button>
<button class="tab-btn <?= $currentTab==='contact'?'active':'' ?>" onclick="showTab('contact')">📞 Contact</button>
<button class="tab-btn <?= $currentTab==='settings'?'active':'' ?>" onclick="showTab('settings')">⚙️ Settings</button>
</div>

<div class="tab-content <?= $currentTab==='overview'?'active':'' ?>" id="overview">
<h2>📊 Dashboard Overview</h2>
<div class="stats-grid">
<div class="stat-card">
<div class="stat-number"><?= $stats['total_donations'] ?></div>
<div>Total Donations</div>
</div>
<div class="stat-card">
<div class="stat-number">₹<?= number_format($stats['total_amount'],2) ?></div>
<div>Total Amount</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['pending'] ?></div>
<div>Pending</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['partners'] ?></div>
<div>Partners</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['media_items'] ?></div>
<div>Media Items</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['testimonials'] ?></div>
<div>Testimonials</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['campaigns'] ?></div>
<div>Campaigns</div>
</div>
<div class="stat-card">
<div class="stat-number"><?= $stats['events'] ?></div>
<div>Events</div>
</div>
</div>
<div class="alert alert-info">
<strong>🎉 Welcome!</strong> You have complete control over your website. Use the tabs above to manage content.
</div>
</div>

<div class="tab-content <?= $currentTab==='donations'?'active':'' ?>" id="donations">
<h2>💰 Manage Donations</h2>
<?php $dback=http_build_query(array_filter(['dq'=>$_GET['dq']??'','dstatus'=>$_GET['dstatus']??'','per'=>$_GET['per']??'','page'=>$_GET['page']??''],fn($v)=>$v!=='')); ?>
<form method="GET" class="dfilter">
<input type="hidden" name="tab" value="donations">
<input type="hidden" name="per" value="<?= (int)$dper ?>">
<input type="search" name="dq" value="<?= htmlspecialchars($_GET['dq']??'') ?>" placeholder="🔍 Name / email / phone">
<select name="dstatus" onchange="this.form.submit()">
<option value="">All status</option>
<?php foreach(['Pending','Approved','Rejected'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['dstatus']??'')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
</select>
<button class="btn btn-small" type="submit">Search</button>
<?php if(!empty($_GET['dq'])||!empty($_GET['dstatus'])): ?><a class="btn btn-small" style="background:#64748b;text-decoration:none" href="admin.php?tab=donations">Reset</a><?php endif; ?>
<span class="dexp">
<a class="btn btn-small" style="background:#107c41;text-decoration:none" href="admin.php?export=donations&<?= htmlspecialchars(http_build_query(array_filter(['dq'=>$_GET['dq']??'','dstatus'=>$_GET['dstatus']??''],fn($v)=>$v!==''))) ?>">⬇ Excel (this list)</a>
<a class="btn btn-small" style="background:#475569;text-decoration:none" href="admin.php?export=backup" title="Donations, campaigns, events, partners, testimonials, gallery, contact and settings">💾 Full website backup</a>
</span>
</form>
<p style="color:#64748b;font-size:.9rem;margin:6px 0"><?= number_format($donationTotal) ?> donation<?= $donationTotal===1?'':'s' ?> · Pending ones need your approval before they count in the website totals.</p>
<table>
<tr>
<th>ID</th>
<th>Date</th>
<th>Donor</th>
<th>Amount</th>
<th>Campaign</th>
<th>Method / Ref</th>
<th>Status</th>
<th>Actions</th>
</tr>
<?php foreach($donations as $d):
  $dm=$d['PaymentMethod']??($d['DonationType']??'—'); $dr=$d['TransactionID']??''; $ds_=$d['Status']; ?>
<tr>
<td><?= (int)$d['DonationID'] ?></td>
<td><?= htmlspecialchars(date('d-M-Y H:i',strtotime($d['DonationDate']))) ?></td>
<td><strong><?= htmlspecialchars($d['DonorName']) ?></strong><br><small style="color:#64748b"><?= htmlspecialchars($d['Email']) ?> · <?= htmlspecialchars($d['Phone']) ?></small></td>
<td><strong>₹<?= number_format($d['Amount'],2) ?></strong></td>
<td><?= htmlspecialchars($d['CampaignTitle']??'General') ?></td>
<td><?= htmlspecialchars($dm) ?><?php if($dr): ?><br><small style="color:#64748b"><?= htmlspecialchars($dr) ?></small><?php endif; ?></td>
<td><span class="dbadge d-<?= htmlspecialchars(strtolower($ds_)) ?>"><?= htmlspecialchars($ds_) ?></span></td>
<td>
<form method="POST" style="display:inline">
<input type="hidden" name="id" value="<?= (int)$d['DonationID'] ?>">
<input type="hidden" name="back" value="<?= htmlspecialchars($dback) ?>">
<?php if($ds_!=='Approved'): ?><button type="submit" name="approve" class="btn-small btn-success">✔ Approve</button><?php endif; ?>
<?php if($ds_!=='Rejected'): ?><button type="submit" name="reject" class="btn-small btn-danger" onclick="return confirm('Reject this donation?')">✖ Reject</button><?php endif; ?>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($donations)): ?><tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:26px">No donations found.</td></tr><?php endif; ?>
</table>
<?php $_GET['tab']='donations'; /* page links must stay on this tab */ ?>
<?= pager($donationTotal,$dpage,$dper) ?>
</div>

<div class="tab-content <?= $currentTab==='media'?'active':'' ?>" id="media">
<h2>🎬 Media Gallery</h2>
<div class="upload-section">
<h3>📤 Upload Media</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-row">
<div class="form-group"><label>File</label><input type="file" name="media_file" accept="image/*,video/*" required></div>
<div class="form-group"><label>Title</label><input type="text" name="title" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Category</label><select name="category"><option value="News">📰 News</option><option value="Events">🎉 Events</option><option value="Education">📚 Education</option><option value="Healthcare">🏥 Healthcare</option></select></div>
<div class="form-group"><label>Order</label><input type="number" name="display_order" value="0"></div>
</div>
<div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
<button type="submit" name="upload_media" class="btn">Upload</button>
</form>
</div>
<div class="media-grid">
<?php foreach($mediaGallery as $m): ?>
<div class="media-item">
<?php if($m['FileType']==='video'): ?><video controls><source src="<?= $m['FilePath'] ?>"></video><?php else: ?><img src="<?= $m['FilePath'] ?>"><?php endif; ?>
<div class="media-overlay"><strong><?= htmlspecialchars($m['Title']) ?></strong><br><button class="btn-small btn-danger" onclick="deleteItem('MediaGallery',<?= $m['MediaID'] ?>)">🗑 Delete</button></div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="tab-content <?= $currentTab==='gallery'?'active':'' ?>" id="gallery">
<h2>📷 Photo Gallery</h2>
<div class="upload-section">
<h3>📸 Upload Photo</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-row">
<div class="form-group"><label>Image</label><input type="file" name="gallery_image" accept="image/*" required></div>
<div class="form-group"><label>Title</label><input type="text" name="title" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Category</label><select name="category"><option value="Education">📚 Education</option><option value="Healthcare">🏥 Healthcare</option><option value="Community">👥 Community</option></select></div>
<div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
</div>
<button type="submit" name="upload_gallery" class="btn">Upload</button>
</form>
</div>
<div class="media-grid">
<?php foreach($gallery as $g): ?>
<div class="media-item"><img src="<?= htmlspecialchars($g['ImagePath']) ?>"><div class="media-overlay"><strong><?= htmlspecialchars($g['Title']) ?></strong><br><button class="btn-small btn-danger" onclick="deleteItem('Gallery',<?= $g['GalleryID'] ?>)">🗑 Delete</button></div></div>
<?php endforeach; ?>
</div>
</div>

<div class="tab-content <?= $currentTab==='testimonials'?'active':'' ?>" id="testimonials">
<h2>💬 Testimonials</h2>
<?php if(isset($_GET['edit']) && $currentTab==='testimonials' && is_numeric($_GET['edit'])){
$editId=(int)$_GET['edit'];
$stmt=$pdo->prepare("SELECT * FROM Testimonials WHERE TestimonialID=? AND IsActive=1");
$stmt->execute([$editId]);
$editItem=$stmt->fetch(PDO::FETCH_ASSOC);
if($editItem){ ?>
<div class="upload-section">
<h3>✏ Edit Testimonial</h3>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $editItem['TestimonialID'] ?>">
<input type="hidden" name="existing_photo" value="<?= $editItem['Photo'] ?>">
<div class="form-row">
<div class="form-group"><label>Name</label><input type="text" name="name" value="<?= htmlspecialchars($editItem['Name']) ?>" required></div>
<div class="form-group"><label>Position</label><input type="text" name="position" value="<?= htmlspecialchars($editItem['Position']) ?>"></div>
</div>
<div class="form-group"><label>Photo (leave empty to keep current)</label><input type="file" name="photo" accept="image/*"></div>
<div class="form-group"><label>Message</label><textarea name="message" rows="4" required><?= htmlspecialchars($editItem['Message']) ?></textarea></div>
<button type="submit" name="edit_testimonial" class="btn">✔ Update Testimonial</button>
<a href="admin.php?tab=testimonials" class="btn btn-danger" style="display:inline-block;text-decoration:none;text-align:center">✖ Cancel</a>
</form>
</div>
<?php } } else { ?>
<div class="upload-section">
<h3>➕ Add Testimonial</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-row">
<div class="form-group"><label>Name</label><input type="text" name="name" required></div>
<div class="form-group"><label>Position</label><input type="text" name="position"></div>
</div>
<div class="form-group"><label>Photo</label><input type="file" name="photo" accept="image/*"></div>
<div class="form-group"><label>Message</label><textarea name="message" rows="4" required></textarea></div>
<div class="form-group"><label>Order</label><input type="number" name="display_order" value="0"></div>
<button type="submit" name="add_testimonial" class="btn">Add</button>
</form>
</div>
<?php } ?>
<table>
<tr><th>Photo</th><th>Name</th><th>Position</th><th>Message</th><th>Actions</th></tr>
<?php foreach($testimonials as $t): ?>
<tr><td><?= $t['Photo']?'<img src="'.$t['Photo'].'" style="width:50px;height:50px;border-radius:50%">':'👤' ?></td><td><?= htmlspecialchars($t['Name']) ?></td><td><?= htmlspecialchars($t['Position']) ?></td><td><?= substr(htmlspecialchars($t['Message']),0,50) ?>...</td><td><button class="btn-small btn-success" onclick="editTestimonial(<?= $t['TestimonialID'] ?>)">✏ Edit</button><button class="btn-small btn-danger" onclick="deleteItem('Testimonials',<?= $t['TestimonialID'] ?>)">🗑 Delete</button></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="tab-content <?= $currentTab==='campaigns'?'active':'' ?>" id="campaigns">
<h2>🎯 Campaigns</h2>
<?php if(isset($_GET['edit']) && $currentTab==='campaigns' && is_numeric($_GET['edit'])){
$editId=(int)$_GET['edit'];
$stmt=$pdo->prepare("SELECT * FROM Campaigns WHERE CampaignID=? AND IsActive=1");
$stmt->execute([$editId]);
$editItem=$stmt->fetch(PDO::FETCH_ASSOC);
if($editItem){ ?>
<div class="upload-section">
<h3>✏ Edit Campaign</h3>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $editItem['CampaignID'] ?>">
<input type="hidden" name="existing_image" value="<?= $editItem['ImagePath'] ?>">
<div class="form-row">
<div class="form-group"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($editItem['Title']) ?>" required></div>
<div class="form-group"><label>Target Amount (₹)</label><input type="number" name="target_amount" value="<?= $editItem['TargetAmount'] ?>" required></div>
</div>
<div class="form-group"><label>Image (leave empty to keep current)</label><input type="file" name="image" accept="image/*"></div>
<div class="form-group"><label>Description</label><textarea name="description" rows="4" required><?= htmlspecialchars($editItem['Description']) ?></textarea></div>
<div class="form-group"><label><input type="checkbox" name="is_featured" <?= $editItem['IsFeatured']?'checked':'' ?>> ⭐ Feature on homepage</label></div>
<button type="submit" name="edit_campaign" class="btn">✔ Update Campaign</button>
<a href="admin.php?tab=campaigns" class="btn btn-danger" style="display:inline-block;text-decoration:none;text-align:center">✖ Cancel</a>
</form>
</div>
<?php } } else { ?>
<div class="upload-section">
<h3>🚀 Create Campaign</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-row">
<div class="form-group"><label>Title</label><input type="text" name="title" required></div>
<div class="form-group"><label>Target Amount (₹)</label><input type="number" name="target_amount" required></div>
</div>
<div class="form-group"><label>Image</label><input type="file" name="image" accept="image/*"></div>
<div class="form-group"><label>Description</label><textarea name="description" rows="4" required></textarea></div>
<div class="form-group"><label><input type="checkbox" name="is_featured"> ⭐ Feature on homepage</label></div>
<button type="submit" name="add_campaign" class="btn">Create</button>
</form>
</div>
<?php } ?>
<table>
<tr><th>Title</th><th>Target</th><th>Current</th><th>Featured</th><th>Actions</th></tr>
<?php foreach($campaigns as $c): ?>
<tr><td><?= htmlspecialchars($c['Title']) ?></td><td>₹<?= number_format($c['TargetAmount']) ?></td><td>₹<?= number_format($c['CurrentAmount']) ?></td><td><?= $c['IsFeatured']?'⭐':'➖' ?></td><td><button class="btn-small btn-success" onclick="editCampaign(<?= $c['CampaignID'] ?>)">✏ Edit</button><button class="btn-small btn-danger" onclick="deleteItem('Campaigns',<?= $c['CampaignID'] ?>)">🗑 Delete</button></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="tab-content <?= $currentTab==='events'?'active':'' ?>" id="events">
<h2>📅 Events</h2>
<?php if(isset($_GET['edit']) && $currentTab==='events' && is_numeric($_GET['edit'])){
$editId=(int)$_GET['edit'];
$stmt=$pdo->prepare("SELECT * FROM Events WHERE EventID=? AND IsActive=1");
$stmt->execute([$editId]);
$editItem=$stmt->fetch(PDO::FETCH_ASSOC);
if($editItem){ ?>
<div class="upload-section">
<h3>✏ Edit Event</h3>
<form method="POST">
<input type="hidden" name="id" value="<?= $editItem['EventID'] ?>">
<div class="form-row">
<div class="form-group"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($editItem['Title']) ?>" required></div>
<div class="form-group"><label>Date</label><input type="date" name="event_date" value="<?= $editItem['EventDate'] ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Location</label><input type="text" name="location" value="<?= htmlspecialchars($editItem['Location']) ?>"></div>
<div class="form-group"><label><input type="checkbox" name="show_popup" <?= $editItem['ShowPopup']?'checked':'' ?>> 🔔 Show popup</label></div>
</div>
<div class="form-group"><label>Description</label><textarea name="description" rows="3" required><?= htmlspecialchars($editItem['Description']) ?></textarea></div>
<button type="submit" name="edit_event" class="btn">✔ Update Event</button>
<a href="admin.php?tab=events" class="btn btn-danger" style="display:inline-block;text-decoration:none;text-align:center">✖ Cancel</a>
</form>
</div>
<?php } } else { ?>
<div class="upload-section">
<h3>➕ Add Event</h3>
<form method="POST">
<div class="form-row">
<div class="form-group"><label>Title</label><input type="text" name="title" required></div>
<div class="form-group"><label>Date</label><input type="date" name="event_date" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Location</label><input type="text" name="location"></div>
<div class="form-group"><label><input type="checkbox" name="show_popup"> 🔔 Show popup</label></div>
</div>
<div class="form-group"><label>Description</label><textarea name="description" rows="3" required></textarea></div>
<button type="submit" name="add_event" class="btn">Add</button>
</form>
</div>
<?php } ?>
<table>
<tr><th>Title</th><th>Date</th><th>Location</th><th>Popup</th><th>Actions</th></tr>
<?php foreach($events as $e): ?>
<tr><td><?= htmlspecialchars($e['Title']) ?></td><td><?= date('M d, Y',strtotime($e['EventDate'])) ?></td><td><?= htmlspecialchars($e['Location']) ?></td><td><?= $e['ShowPopup']?'🔔':'🔕' ?></td><td><button class="btn-small btn-success" onclick="editEvent(<?= $e['EventID'] ?>)">✏ Edit</button><button class="btn-small btn-danger" onclick="deleteItem('Events',<?= $e['EventID'] ?>)">🗑 Delete</button></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="tab-content <?= $currentTab==='partners'?'active':'' ?>" id="partners">
<h2>🤝 Partners</h2>
<div class="upload-section">
<h3>➕ Add Partner</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-row">
<div class="form-group"><label>Name</label><input type="text" name="name" required></div>
<div class="form-group"><label>Mobile Number</label><input type="text" name="mobile" required></div>
</div>
<div class="form-group"><label>Logo/Photo</label><input type="file" name="logo" accept="image/*"></div>
<div class="form-group"><label>Address/Profession</label><textarea name="address" rows="3" required></textarea></div>
<div class="form-group"><label>Display Order</label><input type="number" name="display_order" value="0"></div>
<button type="submit" name="add_partner" class="btn">Add Partner</button>
</form>
</div>
<table>
<tr><th>Logo</th><th>Name</th><th>Mobile</th><th>Address</th><th>Actions</th></tr>
<?php foreach($partners as $p): ?>
<tr id="partner-<?= $p['PartnerID'] ?>">
<td><?= $p['LogoPath']?'<img src="'.$p['LogoPath'].'" style="width:50px;height:50px;border-radius:50%;object-fit:cover">':'👤' ?></td>
<td><?= htmlspecialchars($p['PartnerName']) ?></td>
<td><?= htmlspecialchars($p['WhatsAppNumber']) ?></td>
<td><?= htmlspecialchars($p['ContactInfo']) ?></td>
<td>
<button class="btn-small btn-success" onclick="editPartner(<?= $p['PartnerID'] ?>,'<?= htmlspecialchars($p['PartnerName'],ENT_QUOTES) ?>','<?= htmlspecialchars($p['WhatsAppNumber'],ENT_QUOTES) ?>','<?= htmlspecialchars($p['ContactInfo'],ENT_QUOTES) ?>','<?= htmlspecialchars($p['LogoPath'],ENT_QUOTES) ?>')">✏ Edit</button>
<button class="btn-small btn-danger" onclick="deleteItem('Partners',<?= $p['PartnerID'] ?>)">🗑 Delete</button>
</td>
</tr>
<?php endforeach; ?>
</table>
<div id="editPartnerModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:1000;align-items:center;justify-content:center" onclick="if(event.target===this)closeEditPartner()">
<div style="background:#fff;padding:35px;border-radius:20px;max-width:550px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);position:relative;animation:slideIn 0.3s">
<button onclick="closeEditPartner()" style="position:absolute;top:15px;right:15px;background:transparent;border:none;font-size:24px;cursor:pointer;color:#999;line-height:1">&times;</button>
<h3 style="margin-bottom:25px;color:#6c757d;font-size:1.5rem">✏ Edit Partner</h3>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" id="edit_partner_id">
<input type="hidden" name="existing_logo" id="edit_partner_existing_logo">
<div class="form-group"><label>👤 Name</label><input type="text" name="name" id="edit_partner_name" required></div>
<div class="form-group"><label>📱 Mobile Number</label><input type="text" name="mobile" id="edit_partner_mobile" required></div>
<div class="form-group"><label>🖼 Logo/Photo (leave empty to keep current)</label><input type="file" name="logo" accept="image/*"></div>
<div class="form-group"><label>📍 Address</label><textarea name="address" id="edit_partner_address" rows="3" required></textarea></div>
<div style="display:flex;gap:10px;margin-top:25px">
<button type="submit" name="edit_partner" class="btn" style="flex:1">✔ Update Partner</button>
<button type="button" class="btn btn-danger" onclick="closeEditPartner()" style="flex:1;background:#dc3545">✖ Cancel</button>
</div>
</form>
</div>
</div>
<style>@keyframes slideIn{from{transform:translateY(-50px);opacity:0}to{transform:translateY(0);opacity:1}}</style>
</div>

<div class="tab-content <?= $currentTab==='about'?'active':'' ?>" id="about">
<h2>📖 About Section Settings</h2>
<div class="upload-section">
<h3>✏️ Edit About Content</h3>
<form method="POST">
<div class="form-group">
<label>About Subtitle</label>
<textarea name="about_subtitle" rows="2" required><?= htmlspecialchars($aboutSubtitle??'Transforming lives through compassion, dedication, and sustainable impact across India') ?></textarea>
</div>
<div class="form-row">
<div class="form-group">
<label>Mission Statement</label>
<textarea name="mission_text" rows="4" required><?= htmlspecialchars($missionText??'To empower underprivileged communities through education, healthcare, and sustainable development programs that create lasting positive change.') ?></textarea>
</div>
<div class="form-group">
<label>Vision Statement</label>
<textarea name="vision_text" rows="4" required><?= htmlspecialchars($visionText??'A world where everyone has access to opportunities, dignity, and the chance to build a better future for themselves and their families.') ?></textarea>
</div>
</div>
<h4 style="margin:30px 0 15px;color:#6c757d">📊 Impact Counters</h4>
<div class="form-row">
<div class="form-group">
<label>Lives Impacted</label>
<input type="number" name="lives_impacted" value="<?= htmlspecialchars($livesImpacted??'1000') ?>" required>
</div>
<div class="form-group">
<label>Projects Completed</label>
<input type="number" name="projects_completed" value="<?= htmlspecialchars($projectsCompleted??'50') ?>" required>
</div>
</div>
<div class="form-row">
<div class="form-group">
<label>Communities Served</label>
<input type="number" name="communities_served" value="<?= htmlspecialchars($communitiesServed??'25') ?>" required>
</div>
<div class="form-group">
<label>Years of Service</label>
<input type="number" name="years_of_service" value="<?= htmlspecialchars($yearsOfService??'5') ?>" required>
</div>
</div>
<button type="submit" name="update_about" class="btn">Update About Section</button>
</form>
</div>
</div>

<div class="tab-content <?= $currentTab==='theme'?'active':'' ?>" id="theme">
<h2>🎨 Theme Settings</h2>
<div class="upload-section">
<h3>🎨 Custom Color Picker</h3>
<?php if(isset($_SESSION['theme_success'])): ?>
<div style="background:#d4edda;color:#155724;padding:15px;border-radius:8px;margin-bottom:20px"><?= $_SESSION['theme_success']; unset($_SESSION['theme_success']); ?></div>
<?php endif; ?>
<?php if(isset($_SESSION['theme_error'])): ?>
<div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:8px;margin-bottom:20px"><?= $_SESSION['theme_error']; unset($_SESSION['theme_error']); ?></div>
<?php endif; ?>
<form method="POST">
<div class="form-row">
<div class="form-group"><label>Primary Color</label><input type="color" name="primary_color" value="<?= $primaryColorAdmin?:$themeSettings['BackgroundColor'] ?>" class="color-picker"></div>
<div class="form-group"><label>Secondary Color</label><input type="color" name="secondary_color" value="<?= $secondaryColor??'#495057' ?>" class="color-picker"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Hero Text Color</label><input type="color" name="hero_font_color" value="<?= $heroFontColor ?>" class="color-picker"></div>
</div>
<div class="form-group"><label>Gradient Type</label><select name="gradient_type">
<option value="linear" <?= $gradientType==='linear'?'selected':'' ?>>Linear Gradient</option>
<option value="radial" <?= $gradientType==='radial'?'selected':'' ?>>Radial Gradient</option>
<option value="solid" <?= $gradientType==='solid'?'selected':'' ?>>Solid Color</option>
</select></div>
<div class="form-group"><label>Gradient Direction</label><select name="gradient_direction">
<option value="135deg" <?= $gradientDirection==='135deg'?'selected':'' ?>>Diagonal (Default)</option>
<option value="0deg" <?= $gradientDirection==='0deg'?'selected':'' ?>>Horizontal →</option>
<option value="90deg" <?= $gradientDirection==='90deg'?'selected':'' ?>>Vertical ↓</option>
<option value="45deg" <?= $gradientDirection==='45deg'?'selected':'' ?>>Diagonal ↗</option>
<option value="225deg" <?= $gradientDirection==='225deg'?'selected':'' ?>>Diagonal ↙</option>
</select></div>
<button type="submit" name="update_custom_theme" class="btn">Apply Custom Theme</button>
</form>
<div style="margin-top:20px">
<h4>🎨 Quick Presets</h4>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-top:15px">
<button onclick="applyPreset('#6c757d','#495057','Ocean Gray')" style="background:linear-gradient(135deg,#6c757d,#495057);color:#fff;border:none;padding:15px;border-radius:8px;cursor:pointer">Ocean Gray</button>
<button onclick="applyPreset('#667eea','#764ba2','Blue Purple')" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;padding:15px;border-radius:8px;cursor:pointer">Blue Purple</button>
<button onclick="applyPreset('#11998e','#38ef7d','Green Mint')" style="background:linear-gradient(135deg,#11998e,#38ef7d);color:#fff;border:none;padding:15px;border-radius:8px;cursor:pointer">Green Mint</button>
<button onclick="applyPreset('#ff6b6b','#ffa726','Sunset')" style="background:linear-gradient(135deg,#ff6b6b,#ffa726);color:#fff;border:none;padding:15px;border-radius:8px;cursor:pointer">Sunset</button>
<button onclick="applyPreset('#8e2de2','#4a00e0','Purple Deep')" style="background:linear-gradient(135deg,#8e2de2,#4a00e0);color:#fff;border:none;padding:15px;border-radius:8px;cursor:pointer">Purple Deep</button>
<button onclick="applyPreset('#2c3e50','#34495e','Dark Slate')" style="background:linear-gradient(135deg,#2c3e50,#34495e);color:#fff;border:none;padding:15px;border-radius:8px;cursor:pointer">Dark Slate</button>
</div>
</div>
</div>
<div class="upload-section">
<h3>🏢 Upload Logo</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-group"><label>Logo Image</label><input type="file" name="logo" accept="image/*"></div>
<button type="submit" name="upload_logo" class="btn">Upload Logo</button>
</form>
<div style="margin-top:15px;text-align:center">
<?php if($logoPath && file_exists($logoPath)): ?>
<img src="<?= htmlspecialchars($logoPath) ?>" style="max-width:200px;border-radius:10px;border:2px solid #ddd">
<form method="POST" style="margin-top:10px">
<button type="submit" name="delete_logo" class="btn-small btn-danger" onclick="return confirm('Delete logo? This cannot be undone.')">🗑 Delete Logo</button>
</form>
<?php else: ?>
<div style="padding:40px;background:#f8f9fa;border-radius:10px;color:#999">🏢 No Logo Uploaded</div>
<?php endif; ?>
</div>
</div>
<div class="upload-section">
<h3>🎬 Upload Hero Video</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-group"><label>Video File (MP4/WebM/MOV)</label><input type="file" name="hero_video" accept="video/*"></div>
<button type="submit" name="upload_hero_video" class="btn">Upload Video</button>
</form>
<div style="margin-top:15px;text-align:center">
<?php if($heroVideo && file_exists($heroVideo)): ?>
<video src="<?= htmlspecialchars($heroVideo) ?>" style="max-width:300px;border-radius:10px;border:2px solid #ddd" controls></video>
<form method="POST" style="margin-top:10px">
<button type="submit" name="delete_hero_video" class="btn-small btn-danger" onclick="return confirm('Delete hero video? This cannot be undone.')">🗑 Delete Video</button>
</form>
<?php else: ?>
<div style="padding:60px;background:#f8f9fa;border-radius:10px;color:#999">🎬 No Video Uploaded</div>
<?php endif; ?>
</div>
</div>
<div class="upload-section">
<h3>📝 Site Heading & Font</h3>
<form method="POST">
<div class="form-row">
<div class="form-group"><label>Heading Text</label><input type="text" name="heading" value="<?= htmlspecialchars($siteHeading) ?>" required></div>
<div class="form-group"><label>Font Color</label><input type="color" name="font_color" value="<?= $fontColor ?>" class="color-picker"></div>
</div>
<button type="submit" name="update_heading" class="btn">Update Heading & Font</button>
</form>
</div>
<div class="upload-section">
<h3>🖼️ Background Image</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-group"><label>Background Image</label><input type="file" name="background" accept="image/*"></div>
<div class="form-group"><label>Image Opacity</label><input type="range" name="img_opacity" min="0" max="1" step="0.1" value="<?= $imgOpacity ?>" oninput="this.nextElementSibling.textContent=this.value"><span><?= $imgOpacity ?></span></div>
<button type="submit" name="upload_bg" class="btn">Upload Background</button>
</form>
<div style="margin-top:15px;text-align:center">
<?php if($bgPath && file_exists($bgPath)): ?>
<img src="<?= htmlspecialchars($bgPath) ?>" style="max-width:300px;border-radius:10px;border:2px solid #ddd">
<form method="POST" style="margin-top:10px">
<button type="submit" name="delete_background" class="btn-small btn-danger" onclick="return confirm('Delete background image? This cannot be undone.')">🗑 Delete Background</button>
</form>
<?php else: ?>
<div style="padding:60px;background:#f8f9fa;border-radius:10px;color:#999">🖼️ No Background Uploaded</div>
<?php endif; ?>
</div>
</div>
<div class="upload-section">
<h3>🎨 Customize Theme</h3>
<form method="POST">
<div class="form-row">
<div class="form-group"><label>Primary Color</label><input type="color" name="bg_color" class="color-picker" value="<?= $themeSettings['BackgroundColor'] ?>"></div>
<div class="form-group"><label>Hero Title</label><input type="text" name="hero_title" value="<?= htmlspecialchars($themeSettings['HeroTitle']) ?>"></div>
</div>
<div class="form-group"><label>Hero Subtitle</label><input type="text" name="hero_subtitle" value="<?= htmlspecialchars($themeSettings['HeroSubtitle']) ?>"></div>
<button type="submit" name="update_theme" class="btn">Update Theme</button>
</form>
</div>
<div class="upload-section">
<h3>👁️ Section Visibility & Order</h3>
<form method="POST">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:20px">
<div class="preview-card"><label style="display:flex;justify-content:space-between;align-items:center"><span>📖 About</span><div style="display:flex;gap:10px;align-items:center"><input type="number" name="about_order" min="1" max="5" value="<?= $pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_about'")->fetchColumn()??1 ?>" style="width:50px"><label class="toggle-switch"><input type="checkbox" <?= $themeSettings['ShowAboutSection']?'checked':'' ?> onchange="toggleSection('ShowAboutSection',this.checked)"><span class="slider"></span></label></div></label></div>
<div class="preview-card"><label style="display:flex;justify-content:space-between;align-items:center"><span>📊 Stats</span><div style="display:flex;gap:10px;align-items:center"><input type="number" name="stats_order" min="1" max="5" value="<?= $pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_stats'")->fetchColumn()??2 ?>" style="width:50px"><label class="toggle-switch"><input type="checkbox" <?= $themeSettings['ShowStatsSection']?'checked':'' ?> onchange="toggleSection('ShowStatsSection',this.checked)"><span class="slider"></span></label></div></label></div>
<div class="preview-card"><label style="display:flex;justify-content:space-between;align-items:center"><span>🎯 Programs</span><div style="display:flex;gap:10px;align-items:center"><input type="number" name="programs_order" min="1" max="5" value="<?= $pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_programs'")->fetchColumn()??3 ?>" style="width:50px"><label class="toggle-switch"><input type="checkbox" <?= $themeSettings['ShowProgramsSection']?'checked':'' ?> onchange="toggleSection('ShowProgramsSection',this.checked)"><span class="slider"></span></label></div></label></div>
<div class="preview-card"><label style="display:flex;justify-content:space-between;align-items:center"><span>🖼️ Gallery</span><div style="display:flex;gap:10px;align-items:center"><input type="number" name="gallery_order" min="1" max="5" value="<?= $pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_gallery'")->fetchColumn()??4 ?>" style="width:50px"><label class="toggle-switch"><input type="checkbox" <?= $themeSettings['ShowGallerySection']?'checked':'' ?> onchange="toggleSection('ShowGallerySection',this.checked)"><span class="slider"></span></label></div></label></div>
<div class="preview-card"><label style="display:flex;justify-content:space-between;align-items:center"><span>🤝 Partners</span><div style="display:flex;gap:10px;align-items:center"><input type="number" name="partners_order" min="1" max="5" value="<?= $pdo->query("SELECT SettingValue FROM AdminSettings WHERE SettingKey='SectionOrder_partners'")->fetchColumn()??5 ?>" style="width:50px"><label class="toggle-switch"><input type="checkbox" <?= $themeSettings['ShowPartnersSection']?'checked':'' ?> onchange="toggleSection('ShowPartnersSection',this.checked)"><span class="slider"></span></label></div></label></div>
</div>
<div style="display:flex;gap:10px">
<button type="submit" name="update_section_order" class="btn">Update Section Order</button>
<button type="submit" name="clear_section_order" class="btn btn-danger" onclick="return confirm('Clear all section orders?')">Clear All</button>
</div>
</form>
</div>
</div>

<div class="tab-content <?= $currentTab==='contact'?'active':'' ?>" id="contact">
<h2>📞 Contact Info</h2>
<div class="upload-section">
<form method="POST">
<div class="form-row">
<div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($contact['Phone']??'') ?>"></div>
<div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($contact['Email']??'') ?>"></div>
</div>
<div class="form-group"><label>Address</label><textarea name="address" rows="3"><?= htmlspecialchars($contact['Address']??'') ?></textarea></div>
<div class="form-row">
<div class="form-group"><label>Facebook</label><input type="url" name="facebook" value="<?= htmlspecialchars($contact['FacebookURL']??'') ?>"></div>
<div class="form-group"><label>Twitter</label><input type="url" name="twitter" value="<?= htmlspecialchars($contact['TwitterURL']??'') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Instagram</label><input type="url" name="instagram" value="<?= htmlspecialchars($contact['InstagramURL']??'') ?>"></div>
<div class="form-group"><label>LinkedIn</label><input type="url" name="linkedin" value="<?= htmlspecialchars($contact['LinkedInURL']??'') ?>"></div>
</div>
<div class="form-group"><label>WhatsApp</label><input type="text" name="whatsapp" value="<?= htmlspecialchars($contact['WhatsAppNumber']??'') ?>"></div>
<button type="submit" name="update_contact" class="btn">Update</button>
</form>
</div>
</div>

<div class="tab-content <?= $currentTab==='settings'?'active':'' ?>" id="settings">
<h2>⚙️ Settings</h2>
<div class="upload-section">
<h3>💳 UPI Payment Settings</h3>
<form method="POST">
<div class="form-group">
<label>UPI ID</label>
<input type="text" name="upi_id" value="<?= htmlspecialchars($upiId??'reliefwave@paytm') ?>" placeholder="yourname@paytm" required>
</div>
<button type="submit" name="update_upi" class="btn">Update UPI ID</button>
</form>
</div>
<div class="upload-section">
<h3>💳 Upload QR Code</h3>
<form method="POST" enctype="multipart/form-data">
<div class="form-group">
<label>QR Code Image</label>
<input type="file" name="qr_code" accept="image/*">
</div>
<button type="submit" name="upload_qr" class="btn">Upload</button>
</form>
<div style="margin-top:20px;text-align:center">
<p><strong>Current QR Code:</strong></p>
<?php if($qrCode && file_exists($qrCode)): ?>
<img src="<?= htmlspecialchars($qrCode) ?>" style="max-width:200px;margin-top:10px;border-radius:10px;border:2px solid #ddd">
<?php else: ?>
<div style="padding:60px;background:#f8f9fa;border-radius:10px;color:#999;margin-top:10px">📷 No QR Code Uploaded</div>
<?php endif; ?>
</div>
</div>
</div>

</div>
<?php endif; ?>

<script>
function showTab(tab){
document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
event.target.classList.add('active');
document.getElementById(tab).classList.add('active');
const url=new URL(window.location);
url.searchParams.set('tab',tab);
window.history.pushState({},'',url);
}
function deleteItem(table,id){
if(!confirm('Delete this item?'))return;
fetch('admin.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`ajax=1&action=delete_item&table=${table}&id=${id}`
}).then(r=>r.json()).then(d=>{
if(d.success){alert('✅ Deleted!');location.reload();}else{alert('❌ Error: '+(d.error||'Unknown error'));}
}).catch(e=>{alert('❌ Network error: '+e);});
}
function editPartner(id,name,mobile,address,logo){
document.getElementById('edit_partner_id').value=id;
document.getElementById('edit_partner_name').value=name;
document.getElementById('edit_partner_mobile').value=mobile;
document.getElementById('edit_partner_address').value=address;
document.getElementById('edit_partner_existing_logo').value=logo;
document.getElementById('editPartnerModal').style.display='flex';
}
function closeEditPartner(){
document.getElementById('editPartnerModal').style.display='none';
}
function editCampaign(id){
window.location.href='admin.php?tab=campaigns&edit='+id;
}
function editEvent(id){
window.location.href='admin.php?tab=events&edit='+id;
}
function editTestimonial(id){
window.location.href='admin.php?tab=testimonials&edit='+id;
}
function toggleSection(section,value){
fetch('admin.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`ajax=1&action=toggle_section&section=${section}&value=${value?1:0}`
}).then(r=>r.json()).then(d=>{
if(d.success)alert('✅ Updated!');
});
}
function updateTheme(){
const form=document.getElementById('themeForm');
const data=new FormData(form);
data.append('ajax','1');
data.append('action','update_theme');
fetch('admin.php',{
method:'POST',
body:new URLSearchParams(data)
}).then(r=>r.json()).then(d=>{
if(d.success){alert('✅ Theme updated!');location.reload();}
});
}
function saveSectionOrder(){
const sections = Array.from(document.querySelectorAll('input[data-section]')).map(input => ({
section: input.dataset.section,
order: parseInt(input.value) || 1
})).filter(s => s.order);
if(sections.length === 0) {
alert('Please enter order numbers for sections');
return;
}
fetch('admin.php', {
method: 'POST',
headers: {'Content-Type': 'application/x-www-form-urlencoded'},
body: `ajax=1&action=save_section_order&sections=${encodeURIComponent(JSON.stringify(sections))}`
}).then(r => r.json()).then(d => {
if(d.success) {
alert('✅ Section order updated!');
location.reload();
} else {
alert('❌ Error updating order');
}
});
}

// Drag and drop for sections
let draggedElement = null;
document.querySelectorAll('.preview-card[draggable="true"]').forEach(card => {
card.addEventListener('dragstart', function(e) {
draggedElement = this;
this.style.opacity = '0.5';
});
card.addEventListener('dragend', function(e) {
this.style.opacity = '';
draggedElement = null;
});
card.addEventListener('dragover', function(e) {
e.preventDefault();
});
card.addEventListener('drop', function(e) {
e.preventDefault();
if (draggedElement !== this) {
const parent = this.parentNode;
const draggedIndex = Array.from(parent.children).indexOf(draggedElement);
const targetIndex = Array.from(parent.children).indexOf(this);
if (draggedIndex < targetIndex) {
parent.insertBefore(draggedElement, this.nextSibling);
} else {
parent.insertBefore(draggedElement, this);
}
}
});
});
function saveSectionOrder(){
const sections = Array.from(document.querySelectorAll('.preview-card[data-section]')).map((card, index) => ({
section: card.dataset.section,
order: index + 1
}));
console.log('Saving sections:', sections);
fetch('admin.php', {
method: 'POST',
headers: {'Content-Type': 'application/x-www-form-urlencoded'},
body: `ajax=1&action=save_section_order&sections=${encodeURIComponent(JSON.stringify(sections))}`
}).then(r => r.json()).then(d => {
console.log('Response:', d);
if(d.success) alert('✅ Section order updated!');
else alert('❌ Error updating order');
});
}
function updateLivePreview(){
const color = document.querySelector('input[name="bg_color"]').value;
const title = document.getElementById('heroTitleInput').value;
const subtitle = document.getElementById('heroSubtitleInput').value;
document.getElementById('livePreview').style.background = color;
document.getElementById('previewTitle').textContent = title;
document.getElementById('previewSubtitle').textContent = subtitle;
}
</script>
</body>
</html>

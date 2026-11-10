<?php
// SPIKE: page-2 replica with dummy data. px = coordinates of the 892px-wide reference render.
require __DIR__.'/vendor/autoload.php';
$root = 'H:/laragon/www/taqdheer/';
$img  = $root.'resources/images/registration/';
$K = 0.66736; // px -> pt
function pt($px){ global $K; return round($px*$K,2).'pt'; }
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ar($s){ return preg_replace_callback('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}][\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}\x{0020}\x{060C}\x{061F}]*/u', fn($m)=>'<span class="ar">'.trim($m[0]).'</span> ', e($s)) ?: e($s); }
function arx($s){ return '<span class="ar">'.e($s).'</span>'; }
function box($x,$y,$w,$h,$style='',$in=''){ return '<div style="position:absolute;left:'.pt($x).';top:'.pt($y).';width:'.pt($w).';height:'.pt($h).';'.$style.'">'.$in.'</div>'; }
function txt($x,$y,$w,$html,$size=12.5,$style=''){ return '<div style="position:absolute;left:'.pt($x).';top:'.pt($y).';width:'.pt($w).';font-size:'.pt($size).';'.$style.'">'.$html.'</div>'; }
$NAVY='#243475'; $PINK='#e6007e'; $INK='#1d1d1b';
$star='<span style="color:'.$PINK.'">*</span>';
$input = fn($x,$y,$w,$h,$val)=> box($x,$y,$w,$h,"border:".pt(1.6)." solid $NAVY;border-radius:".pt(5).";",
   '<div style="padding:'.pt(5).' '.pt(12).';font-size:'.pt(12.5).';color:'.$INK.';">'.ar($val).'</div>');
$lab = fn($x,$y,$w,$en,$arb)=> txt($x,$y,$w,e($en).' / '.arx($arb).$star,12.3,"color:$INK;");
$lab2 = fn($x,$y,$w,$en,$arb)=> txt($x,$y,$w,e($en).'<br/>'.arx($arb).$star,12.3,"color:$INK;text-align:center;line-height:1.25;");
$badge = fn($x,$y,$n)=> box($x,$y,30,30,"background:$PINK;color:#fff;border-radius:".pt(15).";border:".pt(2)." solid #fff;text-align:center;font-weight:bold;font-size:".pt(18).";line-height:1;padding-top:".pt(3).";",$n);
$tab = fn($x,$y,$w,$en,$arb,$n)=> box($x+22,$y+3,$w,22,"background:$NAVY;border-radius:0 ".pt(14)." 0 0;","")
   . txt($x+34,$y+6,$w,'<b>'.$en.'</b>'.($arb?' / '.arx($arb):''),13.5,"color:#fff;") . $badge($x+2,$y-1,$n);
$cb = fn($x,$y,$on=false)=> box($x,$y,22,22,"border:".pt(1.8)." solid $NAVY;border-radius:".pt(3).";background:#fff;",$on?'<div style="color:'.$PINK.';font-weight:bold;font-size:'.pt(17).';text-align:center;line-height:1;padding-top:'.pt(1).'">&#10003;</div>':'');
$card = fn($y,$h)=> box(19,$y,858,$h,"border:".pt(1.4)." solid $NAVY;border-radius:".pt(7).";");
$field = fn($x,$y,$w,$h)=> "border:".pt(1.6)." solid $NAVY;border-radius:".pt(5).";";

// ---------- dummy data ----------
$d = ['shop'=>'Al Noor General Trading LLC','owner'=>'Mohammed Abdullah Al Mansoori','passport'=>'N1234567','uid'=>'1020304050','eid'=>'784-1985-1234567-1',
 'tl'=>'TL-2024-998877','email'=>'owner@alnoor-trading.ae','mobile'=>'50 123 4567','wa'=>'50 123 4567','addr'=>'Shop 12, Al Rigga Road, Deira, Dubai  دبي - ديرة - شارع الرقة',
 'map'=>'https://maps.google.com/?q=25.2697,55.3095','products'=>['chocolate'=>1,'attar'=>0,'tasbih'=>1,'pen'=>0,'others'=>1],'other_txt'=>'Dates & gifts',
 'dev'=>['tab'=>1,'pos'=>1,'sim'=>1,'data'=>0],'sim_provided'=>1,'ddate'=>'12 Nov 2026','dtime'=>'11:30 AM','sdate'=>'10 Nov 2026','place'=>'Dubai'];
$terms = ['The Sales Point / Authorized Person agrees to promote and sell Wadi Shukran products as per company policy and guidelines.',
 'The Sales Point / Authorized Person agrees to keep the provided device(s) in good condition and use them only for Wadi Shukran business.',
 'The Sales Point / Authorized Person agrees to provide accurate information and cooperate with the company at all times.',
 'Any loss, damage or misuse of company property (device, SIM, etc.) will be the responsibility of the Sales Point / Authorized Person.',
 'If the Sales Point / Authorized Person takes or receives a SIM Card from Wadi Shukran, all responsibility and liability for that SIM Card shall remain entirely with the Sales Point / Authorized Person from the exact date and time of delivery until the SIM Card is finally returned to or collected by Wadi Shukran. Any misuse, unauthorized use, unlawful use, loss, or improper use of the SIM Card during this period shall be the sole responsibility of the Sales Point / Authorized Person. This Agreement is also based on trust (amanah) and breaching it equals to breach of trust.'];

// dummy images (GD)
$tmp=__DIR__.'/tmp'; @mkdir($tmp);
$g=imagecreatetruecolor(300,420); imagefill($g,0,0,imagecolorallocate($g,190,200,215)); $c=imagecolorallocate($g,90,100,125); imagefilledellipse($g,150,150,130,130,$c); imagefilledellipse($g,150,400,260,300,$c); imagepng($g,"$tmp/photo.png");
foreach(['p','c'] as $n){ $s=imagecreatetruecolor(600,150); imagesavealpha($s,true); imagealphablending($s,false); imagefill($s,0,0,imagecolorallocatealpha($s,0,0,0,127)); $k=imagecolorallocate($s,20,30,120); imagesetthickness($s,4);
 $ph=$n=='c'?2:0; for($i=0;$i<500;$i+=4){ imageline($s,50+$i,(int)(75+sin($i/18+$ph)*45),54+$i,(int)(75+sin(($i+4)/18+$ph)*45),$k);} imagepng($s,"$tmp/sig_$n.png"); }
$logo = $root.'public/uploads/company/company-logo1777394230.png';

$h='';
// header
$h.=box(430,27,462,60,"background:$NAVY;");
$h.=box(345,27,110,60,"background:$PINK;border-radius:".pt(30)." 0 0 ".pt(30).";");
$h.=box(450,27,442,60,"background:$NAVY;");
$h.=txt(405,28,480,arx('استمارة تسجيل نقطة البيع والاتفاقية وادي شكران'),21,"color:#fff;text-align:center;font-weight:bold;");
$h.=txt(405,57,480,'SALES POINT REGISTRATION &amp; AGREEMENT FORM',16.5,"color:#fff;text-align:center;font-weight:bold;");
$h.=box(14,18,62,66,'','<img src="'.$logo.'" style="height:'.pt(66).'" />');
$h.=txt(80,38,300,'WADI SHUKRAN',37,"color:$NAVY;font-weight:bold;");
// S1
$h.=$card(107,355).$tab(19,110,250,'BUSINESS INFORMATION','',1);
$h.=$lab2(37,138,125,'Business Shop Name','اسم المتجر التجاري').$input(167,139,503,27,$d['shop']);
$h.=$lab2(37,177,190,'Authorized Person / Owner Name','اسم الشخص المفوض / المالك').$input(233,178,437,27,$d['owner']);
$h.=$lab(36,216,230,'Passport Number','رقم جواز السفر').$input(36,233,209,27,$d['passport']);
$h.=$lab(262,216,230,'UID Number','رقم الموحد').$input(258,233,210,27,$d['uid']);
$h.=$lab(483,216,230,'E-ID Number','رقم الهوية').$input(481,233,189,27,$d['eid']);
$h.=$lab(33,269,250,'Trade License Number','رقم الرخصة التجارية').$input(33,286,255,27,$d['tl']);
$h.=$lab(308,269,250,'Email Address','عنوان البريد الإلكتروني').$input(303,286,367,27,$d['email']);
$h.=$lab(35,324,250,'Mobile Number','رقم الهاتف');
$h.=box(35,341,291,28,$field(0,0,0,0)).'<img src="'.$img.'flag_uae.png" style="position:absolute;left:'.pt(44).';top:'.pt(348).';width:'.pt(22).'"/>'.txt(74,347,40,'+971',12.5).box(105,341,1,28,"background:$NAVY;").txt(118,347,200,e($d['mobile']),12.5);
$h.=$lab(349,324,250,'Whatsapp Number','رقم واتساب');
$h.=box(349,341,319,28,$field(0,0,0,0)).'<img src="'.$img.'flag_uae.png" style="position:absolute;left:'.pt(358).';top:'.pt(348).';width:'.pt(22).'"/>'.txt(388,347,40,'+971',12.5).box(422,341,1,28,"background:$NAVY;").txt(434,347,200,e($d['wa']),12.5);
$h.=$lab2(38,382,125,'Full Business Address /','العنوان').$input(166,385,694,27,$d['addr']);
$h.=$lab(24,430,140,'Location Map','خريطة الموقع').$input(166,425,694,27,$d['map']).'<img src="'.$img.'pin_icon.png" style="position:absolute;left:'.pt(837).';top:'.pt(431).';width:'.pt(14).'"/>';
// photo
$h.=box(692,139,162,230,"background:#e9e9e9;border:".pt(1.6)." solid $NAVY;",'<img src="'.$tmp.'/photo.png" style="width:'.pt(162).';height:'.pt(230).'"/>');
$h.=txt(670,375,205,arx('صورة الشخص المفوض').'<br/>Authorized Person Photo',13,"text-align:center;line-height:1.25;color:$INK;");
// S2
$h.=$card(473,87).$tab(19,476,300,'PRODUCT INFORMATION','معلومات المنتجات',2);
$px=[['chocolate','Chocolate','شوكولاتة',38],['attar','Attar','عطر',175],['tasbih','Tasbih','مسبحة',283],['pen','Pen','قلم',406]];
foreach($px as [$k,$en,$arb,$x]){ $h.='<img src="'.$img.$k.'.png" style="position:absolute;left:'.pt($x).';top:'.pt(508).';height:'.pt(34).'"/>'; if($d['products'][$k]) $h.=box($x+22,508,16,16,"background:$PINK;color:#fff;border-radius:".pt(8).";text-align:center;font-size:".pt(11).";line-height:1;padding-top:".pt(1).";",'&#10003;'); $h.=txt($x+34,516,110,e($en).' / '.arx($arb),12.5); }
$h.=$cb(504,513,$d['products']['others']).txt(534,517,80,'Others / '.arx('أخرى'),12.5).$input(618,509,228,29,$d['other_txt']);
// S3
$h.=$card(567,123).$tab(19,570,370,'DEVICE &amp; SERVICE ALLOCATION','تخصيص الأجهزة والخدمات',3);
$h.=box(30,597,838,44,"background:#bcc1cc;");
foreach([[46,'Tab','جهاز لوحي','tab'],[158,'POS Machine','جهاز نقط البيع','pos'],[382,'SIM Card','شريحة الاتصال','sim'],[536,'Active Data','بيانات مفعلة','data']] as [$x,$en,$arb,$k]) $h.=$cb($x,607,$d['dev'][$k]).txt($x+30,611,200,e($en).' / '.arx($arb),12.5);
$h.=box(30,641,838,36,"background:#fbe3ef;");
$h.=$cb(46,648,$d['sim_provided']).txt(76,641,125,'SIM Card Provided<br/>'.arx('تم توفير شريحة'),12.5,"line-height:1.2;text-align:center;");
$h.=txt(196,651,160,'Date of Delivery / '.arx('تاريخ التسليم').$star,12.3);
$h.=box(349,642,203,27,$field(0,0,0,0)."background:#fff;").'<img src="'.$img.'calendar_icon.png" style="position:absolute;left:'.pt(358).';top:'.pt(646).';height:'.pt(18).'"/>'.txt(385,648,160,e($d['ddate']),12.5);
$h.=txt(580,642,110,'Time of Delivery<br/>'.arx('وقت التسليم').$star,12.3,"text-align:center;line-height:1.2;");
$h.=box(689,642,159,27,$field(0,0,0,0)."background:#fff;").'<img src="'.$img.'clock_icon.png" style="position:absolute;left:'.pt(697).';top:'.pt(645).';height:'.pt(20).'"/>'.txt(725,648,120,e($d['dtime']),12.5);
// S4
$h.=$card(702,353).$tab(19,705,285,'AGREEMENT TERMS','شروط الاتفاقية',4);
$t='';
foreach($terms as $i=>$tx) $t.='<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:'.pt(11).'"><tr><td width="'.pt(36).'" valign="top"><table width="'.pt(22).'" cellpadding="0" cellspacing="0"><tr><td style="background-color:'.$PINK.';border-radius:'.pt(11).';height:'.pt(22).';text-align:center;color:#fff;font-weight:bold;font-size:'.pt(13).'">'.($i+1).'</td></tr></table></td><td valign="top" style="font-size:'.pt(16).';line-height:1.13;text-align:justify;color:'.$INK.'">'.e($tx).'</td></tr></table>';
$h.=box(45,738,810,300,'',$t);
// S5 signatures
$h.=$card(1066,180).$tab(19,1069,215,'SIGNATURES','التوقيعات',6);
$h.=txt(35,1097,420,'Authorized Person / Sales Point Signature / '.arx('توقيع الشخص المفوض / نقط البيع').$star,11.2);
$h.=txt(458,1097,420,'Company Representative Signature / '.arx('توقيع مندوب الشركة').$star,11.2);
$h.=box(32,1116,407,61,"border:".pt(1.6)." solid $NAVY;border-radius:".pt(6).";",'<img src="'.$tmp.'/sig_p.png" style="height:'.pt(54).';margin-left:'.pt(120).'"/>');
$h.=box(456,1116,410,61,"border:".pt(1.6)." solid $NAVY;border-radius:".pt(6).";",'<img src="'.$tmp.'/sig_c.png" style="height:'.pt(54).';margin-left:'.pt(120).'"/>');
$h.=txt(33,1182,200,'Date / '.arx('التاريخ').$star,12);
$h.=txt(287,1182,200,'Place / '.arx('الموقع').$star,12);
$h.=box(32,1201,239,33,$field(0,0,0,0)).'<img src="'.$img.'calendar_icon.png" style="position:absolute;left:'.pt(42).';top:'.pt(1209).';height:'.pt(18).'"/>'.txt(82,1209,180,e($d['sdate']),12);
$h.=box(284,1201,368,33,$field(0,0,0,0)).txt(298,1209,300,e($d['place']),12);

$css="body{font-family:liberationsans;color:$INK;} .ar{font-family:tajawal;direction:rtl;font-size:1.05em;}";
$cfgDef=(new Mpdf\Config\ConfigVariables())->getDefaults(); $ffDef=(new Mpdf\Config\FontVariables())->getDefaults();
@mkdir(__DIR__.'/mpdftmp');
$mpdf=new Mpdf\Mpdf(['mode'=>'utf-8','format'=>'A4','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'margin_header'=>0,'margin_footer'=>0,'autoPageBreak'=>false,
 'tempDir'=>__DIR__.'/mpdftmp','fontDir'=>array_merge($cfgDef['fontDir'],[$root.'resources/fonts']),
 'fontdata'=>$ffDef['fontdata']+['liberationsans'=>['R'=>'LiberationSans-Regular.ttf','B'=>'LiberationSans-Bold.ttf'],'tajawal'=>['R'=>'Tajawal-Regular.ttf','B'=>'Tajawal-Bold.ttf','useOTL'=>0xFF]],
 'default_font'=>'liberationsans']);
$mpdf->SetDisplayMode('fullpage');
$h=preg_replace('#<img src="([^"]+)" style="position:absolute;left:([^;]+);top:([^;]+);([^"]*)"/>#','<div style="position:absolute;left:$2;top:$3;"><img src="$1" style="$4"/></div>',$h);
$h=str_replace('&#10003;','<span style="font-family:dejavusans">&#10003;</span>',$h);
$mpdf->WriteHTML('<style>'.$css.'</style>'.$h);
file_put_contents(__DIR__.'/out.pdf',$mpdf->Output('','S')); echo "pages=".$mpdf->page."\n";

<?php

namespace App\Services;

use App\Models\SalesPointRegistration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use RuntimeException;

/**
 * Builds the signed "Sales Point Registration & Agreement" PDF (one A4 page, page-2 design of the sample).
 *
 * Engine: mPDF (Arabic shaping + RTL). Fonts: Liberation Sans (Latin) + Tajawal (Arabic), both OFL, in resources/fonts.
 * Layout is absolutely positioned in "reference px" (892px wide render of the sample) and converted to pt.
 *
 * mPDF notes: position:absolute only works on top-level elements (no wrapper div); <img> must be wrapped in an
 * absolutely positioned div; Arabic runs are wrapped in <span class="ar"> so user input is shaped too.
 */
class SalesPointAgreementPdfService
{
    private const K = 0.66736; // reference px -> pt

    private const NAVY = '#243475';

    private const PINK = '#e6007e';

    private const INK = '#1d1d1b';

    /** @var array<string,string> in-memory images (photo/signatures) for the PDF being built */
    private array $imageVars = [];

    /** @var array<string,array{0:int,1:int}> pixel size of each in-memory image, keyed by its `var:` src */
    private array $imageSize = [];

    /** @param  array<int,string>|null  $terms  exact terms to print (snapshot); defaults to current config */
    public function render(SalesPointRegistration $r, ?array $terms = null): string
    {
        $terms = $terms ?: (array) config('sales_point_agreement.terms');
        $this->imageVars = [];
        $this->imageSize = [];
        $html = $this->html($r, $terms);

        $tmp = storage_path('app/mpdf-tmp');
        if (! is_dir($tmp) && ! @mkdir($tmp, 0775, true) && ! is_dir($tmp)) {
            throw new RuntimeException('Cannot create the PDF temp directory.');
        }

        $cfg = (new ConfigVariables)->getDefaults();
        $fonts = (new FontVariables)->getDefaults();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_header' => 0, 'margin_footer' => 0,
            'autoPageBreak' => false,
            'tempDir' => $tmp,
            'fontDir' => array_merge($cfg['fontDir'], [resource_path('fonts')]),
            'fontdata' => $fonts['fontdata'] + [
                'liberationsans' => ['R' => 'LiberationSans-Regular.ttf', 'B' => 'LiberationSans-Bold.ttf'],
                'tajawal' => ['R' => 'Tajawal-Regular.ttf', 'B' => 'Tajawal-Bold.ttf', 'useOTL' => 0xFF],
            ],
            'default_font' => 'liberationsans',
        ]);
        $mpdf->SetTitle('Sales Point Registration & Agreement '.$r->reference_no);
        $mpdf->SetCreator('Wadi Shukran');
        $mpdf->imageVars = $this->imageVars;
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    // ------------------------------------------------------------------ layout

    private function html(SalesPointRegistration $r, array $terms): string
    {
        $img = resource_path('images/registration').'/';
        $N = self::NAVY;
        $P = self::PINK;
        $star = '<span style="color:'.$P.'">*</span>';
        $logo = $this->logoPath();
        $h = '';

        // header
        $h .= $this->box(430, 27, 462, 60, "background:$N;");
        $h .= $this->box(345, 27, 110, 60, "background:$P;border-radius:".$this->pt(30).' 0 0 '.$this->pt(30).';');
        $h .= $this->box(450, 27, 442, 60, "background:$N;");
        $h .= $this->txt(385, 31, 500, $this->arx('استمارة تسجيل نقطة البيع والاتفاقية وادي شكران'), 17, 'color:#fff;text-align:center;font-weight:bold;white-space:nowrap;');
        $h .= $this->txt(385, 57, 500, 'SALES POINT REGISTRATION &amp; AGREEMENT FORM', 16, 'color:#fff;text-align:center;font-weight:bold;white-space:nowrap;');
        [$lw, $lh] = $this->logoBox($logo, 66, 110);
        $h .= $this->img(14, 18 + (66 - $lh) / 2, $logo, 'height:'.$this->pt($lh).';');
        $titleX = 14 + $lw + 12;
        $h .= $this->txt($titleX, 38, 335 - $titleX, 'WADI SHUKRAN', $this->sz(335 - $titleX, 'WADI SHUKRAN', 33, 18, 0.72), "color:$N;font-weight:bold;white-space:nowrap;");

        // 1 business information
        $h .= $this->card(107, 355).$this->tab(19, 110, 250, 'BUSINESS INFORMATION', '', 1);
        $h .= $this->lab2(34, 138, 126, 'Business Shop Name', 'اسم المتجر التجاري').$this->input(167, 139, 503, 27, $r->shop_name);
        $h .= $this->lab2(32, 177, 198, 'Authorized Person / Owner Name', 'اسم الشخص المفوض / المالك').$this->input(233, 178, 437, 27, $r->owner_name);
        $h .= $this->lab(36, 216, 230, 'Passport Number', 'رقم جواز السفر').$this->input(36, 233, 209, 27, $r->passport_no);
        $h .= $this->lab(262, 216, 230, 'UID Number', 'رقم الموحد').$this->input(258, 233, 210, 27, $r->uid_no);
        $h .= $this->lab(483, 216, 230, 'E-ID Number', 'رقم الهوية').$this->input(481, 233, 189, 27, $r->eid_no);
        $h .= $this->lab(33, 269, 270, 'Trade License Number', 'رقم الرخصة التجارية').$this->input(33, 286, 255, 27, $r->trade_license_no);
        $h .= $this->lab(308, 269, 300, 'Email Address', 'عنوان البريد الإلكتروني').$this->input(303, 286, 367, 27, $r->email);
        $h .= $this->lab(35, 324, 250, 'Mobile Number', 'رقم الهاتف').$this->phone(35, 341, 291, $r->mobile_country_code, $r->mobile, $img);
        $h .= $this->lab(349, 324, 250, 'Whatsapp Number', 'رقم واتساب').$this->phone(349, 341, 319, $r->whatsapp_country_code, $r->whatsapp, $img);
        $h .= $this->lab2(32, 382, 130, 'Full Business Address', 'العنوان').$this->input(166, 385, 694, 27, $r->address);
        $h .= $this->lab2(32, 421, 130, 'Location Map', 'خريطة الموقع').$this->input(166, 425, 694, 27, $r->location_map);
        $h .= $this->img(837, 431, $img.'pin_icon.png', 'width:'.$this->pt(14).';');

        // photo
        $photo = $this->absolute($r->photo_path, 900, 900, 162 / 196);
        $h .= $this->box(692, 139, 162, 196, 'background:#e9e9e9;border:'.$this->pt(1.6)." solid $N;", $photo ? '<img src="'.$photo.'" style="width:'.$this->pt(162).';height:'.$this->pt(196).'"/>' : '<img src="'.$img.'camera.png" style="width:'.$this->pt(120).';margin:'.$this->pt(60).' 0 0 '.$this->pt(21).'"/>');
        $h .= $this->txt(670, 345, 205, $this->arx('صورة الشخص المفوض').'<br/>Authorized Person Photo', 13, 'text-align:center;line-height:1.25;color:'.self::INK.';');

        // 2 products
        $p = $r->products ?? [];
        $h .= $this->card(473, 87).$this->tab(19, 476, 340, 'PRODUCT INFORMATION', 'معلومات المنتجات', 2);
        foreach ([['chocolate', 'Chocolate', 'شوكولاتة', 32], ['attar', 'Attar', 'عطر', 172], ['tasbih', 'Tasbih', 'مسبحة', 312], ['pen', 'Pen', 'قلم', 452]] as [$k, $en, $arb, $x]) {
            $h .= $this->check($x, 516, ! empty($p[$k]));
            $h .= $this->img($x + 30, 508, $img.$k.'.png', 'height:'.$this->pt(38).';');
            $h .= $this->txt($x + 76, 515, 64, $this->e($en).'<br/>'.$this->arx($arb), $this->sz(62, $en, 12), 'line-height:1.25;white-space:nowrap;');
        }
        $h .= $this->check(598, 516, ! empty($p['others'])).$this->txt(628, 515, 62, 'Others<br/>'.$this->arx('أخرى'), 12, 'line-height:1.25;');
        $h .= $this->input(694, 513, 166, 29, ! empty($p['others']) ? (string) $r->products_other_text : '');

        // 3 devices
        $h .= $this->card(567, 123).$this->tab(19, 570, 370, 'DEVICE &amp; SERVICE ALLOCATION', 'تخصيص الأجهزة والخدمات', 3);
        $h .= $this->box(30, 603, 838, 42, 'background:#bcc1cc;');
        foreach ([[46, 'Tab', 'جهاز لوحي', 'dev_tab'], [188, 'POS Machine', 'جهاز نقط البيع', 'dev_pos_machine'], [412, 'SIM Card', 'شريحة الاتصال', 'dev_sim_card'], [606, 'Active Data', 'بيانات مفعلة', 'dev_active_data']] as [$x, $en, $arb, $k]) {
            $h .= $this->check($x, 613, (bool) $r->{$k}).$this->txt($x + 30, 617, 200, $this->e($en).' / '.$this->arx($arb), 12.5);
        }
        $h .= $this->box(30, 645, 838, 36, 'background:#fbe3ef;');
        $h .= $this->check(46, 652, (bool) $r->sim_card_provided).$this->txt(76, 647, 140, 'SIM Card Provided<br/>'.$this->arx('تم توفير شريحة'), 12.5, 'line-height:1.2;');
        $h .= $this->txt(216, 649, 150, 'Date of Delivery /<br/>'.$this->arx('تاريخ التسليم').$star, 12, 'line-height:1.2;');
        $h .= $this->box(349, 646, 203, 27, $this->border().'background:#fff;').$this->img(358, 650, $img.'calendar_icon.png', 'height:'.$this->pt(18).';').$this->txt(385, 652, 160, $this->e($this->date($r->delivery_date)), 12.5);
        $h .= $this->txt(580, 647, 110, 'Time of Delivery<br/>'.$this->arx('وقت التسليم').$star, 12, 'text-align:center;line-height:1.2;');
        $h .= $this->box(689, 646, 159, 27, $this->border().'background:#fff;').$this->img(697, 649, $img.'clock_icon.png', 'height:'.$this->pt(20).';').$this->txt(725, 652, 120, $this->e($this->time($r->delivery_time)), 12.5);

        // 4 terms
        $h .= $this->card(702, 353).$this->tab(19, 705, 285, 'AGREEMENT TERMS', 'شروط الاتفاقية', 4);
        $t = '';
        foreach (array_values($terms) as $i => $tx) {
            $t .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:'.$this->pt(11).'"><tr><td width="'.$this->pt(36).'" valign="top">'
                .'<table width="'.$this->pt(22).'" cellpadding="0" cellspacing="0"><tr><td style="background-color:'.self::PINK.';border-radius:'.$this->pt(11).';height:'.$this->pt(22).';text-align:center;color:#fff;font-weight:bold;font-size:'.$this->pt(13).'">'.($i + 1).'</td></tr></table></td>'
                .'<td valign="top" style="font-size:'.$this->pt(16).';line-height:1.13;text-align:left;color:'.self::INK.'">'.$this->e($tx).'</td></tr></table>';
        }
        $h .= $this->box(56, 750, 800, 300, '', $t);

        // 5 signatures (numbered 6 on the form; section 5 "Attach Documents" is not part of the PDF)
        $h .= $this->card(1066, 180).$this->tab(19, 1069, 215, 'SIGNATURES', 'التوقيعات', 6);
        $l1 = 'Authorized Person / Sales Point Signature / توقيع الشخص المفوض / نقط البيع*';
        $l2 = 'Company Representative Signature / توقيع مندوب الشركة*';
        $h .= $this->txt(42, 1097, 398, 'Authorized Person / Sales Point Signature / '.$this->arx('توقيع الشخص المفوض / نقط البيع').$star, $this->sz(398, $l1, 11.2), 'white-space:nowrap;');
        $h .= $this->txt(458, 1097, 405, 'Company Representative Signature / '.$this->arx('توقيع مندوب الشركة').$star, $this->sz(405, $l2, 11.2), 'white-space:nowrap;');
        foreach ([[32, 407, $r->person_signature_path], [456, 410, $r->company_signature_path]] as [$x, $w, $path]) {
            $sig = $this->absolute($path, 1200, 600);
            $in = '';
            if ($sig) {
                // fit inside the box keeping the aspect ratio, centred
                [$iw, $ih] = $this->imageSize[$sig];
                $k = min(($w - 16) / $iw, (61 - 10) / $ih);
                $dw = $iw * $k;
                $dh = $ih * $k;
                $in = '<img src="'.$sig.'" style="width:'.$this->pt($dw).';height:'.$this->pt($dh).';margin:'.$this->pt((61 - $dh) / 2 - 1.6).' 0 0 '.$this->pt(($w - $dw) / 2 - 1.6).'"/>';
            }
            $h .= $this->box($x, 1116, $w, 61, 'border:'.$this->pt(1.6)." solid $N;border-radius:".$this->pt(6).';', $in);
        }
        $h .= $this->txt(33, 1182, 200, 'Date / '.$this->arx('التاريخ').$star, 12);
        $h .= $this->txt(287, 1182, 200, 'Place / '.$this->arx('الموقع').$star, 12);
        $h .= $this->box(32, 1201, 239, 33, $this->border()).$this->img(42, 1209, $img.'calendar_icon.png', 'height:'.$this->pt(18).';').$this->txt(82, 1209, 180, $this->e($this->date($r->signed_date)), 12);
        $h .= $this->box(284, 1201, 368, 33, $this->border()).$this->txt(298, 1209, 340, $this->ar($this->fit($r->signed_place, 48)), 12, 'white-space:nowrap;');

        // reference + approval stamp (small, bottom right)
        $h .= $this->txt(672, 1205, 200, 'Ref: '.$this->e($r->reference_no).($r->approved_at ? '<br/>Approved: '.$this->e($r->approved_at->format('d M Y')) : ''), 10.5, 'color:'.self::NAVY.';line-height:1.3;');

        // mPDF: images wrapped in absolute divs; check mark glyph needs DejaVu
        $h = preg_replace('#<img src="([^"]+)" style="position:absolute;left:([^;]+);top:([^;]+);([^"]*)"/>#', '<div style="position:absolute;left:$2;top:$3;"><img src="$1" style="$4"/></div>', $h);

        if (! is_string($h)) {
            throw new RuntimeException('Could not build the agreement layout.'); // never emit a blank-but-valid PDF
        }

        return '<style>body{font-family:liberationsans;color:'.self::INK.';} .ar{font-family:tajawal;direction:rtl;font-size:1.05em;}</style>'.$h;
    }

    // ------------------------------------------------------------------ helpers

    private function pt(float $px): string
    {
        return round($px * self::K, 2).'pt';
    }

    private function e(?string $s): string
    {
        // drop invisible bidi/format controls (U+202E & co) that could spoof how a name reads, then escape;
        // ENT_SUBSTITUTE keeps invalid UTF-8 from silently blanking the whole value
        $s = preg_replace('/\p{Cf}+/u', '', (string) $s) ?? '';

        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Shorten to what fits the fixed-size box, with an ellipsis (the full value stays on the record). */
    private function fit(?string $s, int $max): string
    {
        $s = trim((string) $s);

        return mb_strlen($s) > $max ? rtrim(mb_substr($s, 0, $max - 1)).'…' : $s;
    }

    /** Escape and wrap every Arabic run so mPDF shapes it with the Arabic font (also for user input). */
    private function ar(string $s): string
    {
        $ar = '\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}';
        $out = preg_replace_callback('/['.$ar.']['.$ar.'\x{0020}\x{060C}\x{061F}]*/u', fn ($m) => '<span class="ar">'.trim($m[0]).'</span> ', $this->e($s));

        return $out ?? $this->e($s);
    }

    private function arx(string $s): string
    {
        return '<span class="ar">'.$this->e($s).'</span>';
    }

    private function box(float $x, float $y, float $w, float $h, string $style = '', string $in = ''): string
    {
        return '<div style="position:absolute;left:'.$this->pt($x).';top:'.$this->pt($y).';width:'.$this->pt($w).';height:'.$this->pt($h).';'.$style.'">'.$in.'</div>';
    }

    private function txt(float $x, float $y, float $w, string $html, float $size = 12.5, string $style = ''): string
    {
        return '<div style="position:absolute;left:'.$this->pt($x).';top:'.$this->pt($y).';width:'.$this->pt($w).';font-size:'.$this->pt($size).';'.$style.'">'.$html.'</div>';
    }

    private function img(float $x, float $y, string $src, string $style = ''): string
    {
        return '<img src="'.$this->e($src).'" style="position:absolute;left:'.$this->pt($x).';top:'.$this->pt($y).';'.$style.'"/>';
    }

    private function border(): string
    {
        return 'border:'.$this->pt(1.6).' solid '.self::NAVY.';border-radius:'.$this->pt(5).';';
    }

    private function input(float $x, float $y, float $w, float $h, ?string $val): string
    {
        return $this->box($x, $y, $w, $h, $this->border().'overflow:hidden;',
            '<div style="padding:'.$this->pt(5).' '.$this->pt(12).';font-size:'.$this->pt(12.5).';color:'.self::INK.';white-space:nowrap;">'.$this->ar($this->fit($val, (int) (($w - 24) / 6.8))).'</div>');
    }

    private function phone(float $x, float $y, float $w, ?string $code, ?string $number, string $img): string
    {
        return $this->box($x, $y, $w, 28, $this->border())
            .$this->img($x + 9, $y + 7, $img.'flag_uae.png', 'width:'.$this->pt(22).';')
            .$this->txt($x + 39, $y + 6, 60, $this->e($this->fit($code ?: '+971', 5)), 12.5)
            .$this->box($x + 70, $y, 1, 28, 'background:'.self::NAVY.';')
            .$this->txt($x + 83, $y + 6, $w - 90, $this->e($this->fit($number, 20)), 12.5, 'white-space:nowrap;');
    }

    /** Font size (px) so that $plain fits in $w px; Arabic glyphs are narrower than Latin on average. */
    private function sz(float $w, string $plain, float $base, float $min = 8, float $latinEm = 0.5): float
    {
        $ar = (int) preg_match_all('/[\x{0600}-\x{06FF}]/u', $plain);
        $em = (mb_strlen($plain) - $ar) * $latinEm + $ar * 0.5;

        return $em > 0 ? max($min, min($base, $w / $em)) : $base;
    }

    /** [width, height] in reference px of the logo scaled to $h tall (capped to $maxW wide). */
    private function logoBox(string $file, float $h, float $maxW): array
    {
        $info = @getimagesize($file);
        $ratio = $info && $info[1] > 0 ? $info[0] / $info[1] : 2.0;
        $w = $h * $ratio;

        return $w > $maxW ? [$maxW, $maxW / $ratio] : [$w, $h];
    }

    private function lab(float $x, float $y, float $w, string $en, string $arb): string
    {
        $size = $this->sz($w - 6, $en.' / '.$arb.'*', 12.3);

        return $this->txt($x, $y, $w, $this->e($en).' / '.$this->arx($arb).'<span style="color:'.self::PINK.'">*</span>', $size, 'color:'.self::INK.';white-space:nowrap;');
    }

    private function lab2(float $x, float $y, float $w, string $en, string $arb): string
    {
        $size = min($this->sz($w - 6, $en, 12.3), $this->sz($w - 6, $arb.'*', 12.3));

        return $this->txt($x, $y, $w, $this->e($en).'<br/>'.$this->arx($arb).'<span style="color:'.self::PINK.'">*</span>', $size, 'color:'.self::INK.';text-align:center;line-height:1.25;white-space:nowrap;');
    }

    private function badge(float $x, float $y, int $n): string
    {
        return $this->box($x, $y, 30, 30, 'background:'.self::PINK.';color:#fff;border-radius:'.$this->pt(15).';border:'.$this->pt(2).' solid #fff;text-align:center;font-weight:bold;font-size:'.$this->pt(18).';line-height:1;padding-top:'.$this->pt(3).';', (string) $n);
    }

    private function tab(float $x, float $y, float $w, string $en, string $arb, int $n): string
    {
        return $this->box($x + 22, $y + 3, $w, 22, 'background:'.self::NAVY.';border-radius:0 '.$this->pt(14).' 0 0;')
            .$this->txt($x + 38, $y + 6, $w, '<b>'.$en.'</b>'.($arb ? ' / '.$this->arx($arb) : ''), 13.5, 'color:#fff;')
            .$this->badge($x + 2, $y - 1, $n);
    }

    private function card(float $y, float $h): string
    {
        return $this->box(19, $y, 858, $h, 'border:'.$this->pt(1.4).' solid '.self::NAVY.';border-radius:'.$this->pt(7).';');
    }

    private function tick(float $size, string $color): string
    {
        return '<div style="color:'.$color.';font-weight:bold;font-size:'.$this->pt($size).';text-align:center;line-height:1;padding-top:'.$this->pt(1).'"><span style="font-family:dejavusans">&#10003;</span></div>';
    }

    private function check(float $x, float $y, bool $on): string
    {
        return $this->box($x, $y, 22, 22, 'border:'.$this->pt(1.8).' solid '.self::NAVY.';border-radius:'.$this->pt(3).';background:#fff;', $on ? $this->tick(17, self::PINK) : '');
    }

    private function date($d): string
    {
        return $d ? Carbon::parse($d)->format('d M Y') : '';
    }

    private function time(?string $t): string
    {
        return $t ? Carbon::createFromFormat('H:i', substr($t, 0, 5))->format('h:i A') : '';
    }

    /**
     * Photo / signature from the private disk as a self-contained `data:` URI (no filesystem path for mPDF to
     * resolve). The image is decoded and re-encoded with GD, so any format/size/EXIF quirk is normalised and a
     * file that cannot be read fails LOUDLY instead of silently producing a PDF without the photo/signature.
     *
     * @return string|null null only when no file was ever recorded (e.g. the optional photo)
     */
    private function absolute(?string $path, int $maxW = 900, int $maxH = 900, ?float $cover = null): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk(config('sales_point_agreement.disk'));
        if (! $disk->exists($path)) {
            throw new RuntimeException("Agreement image is missing on the private disk: {$path}");
        }

        $img = @imagecreatefromstring((string) $disk->get($path));
        if (! $img) {
            throw new RuntimeException("Agreement image could not be decoded: {$path}");
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // optional centre-crop to the frame's aspect ratio (photo) so it is never stretched
        $sx = 0;
        $sy = 0;
        if ($cover) {
            if ($w / $h > $cover) {
                $sx = (int) round(($w - $h * $cover) / 2);
                $w = (int) round($h * $cover);
            } else {
                $sy = (int) round(($h - $w / $cover) / 2);
                $h = (int) round($w / $cover);
            }
        }

        $scale = min(1, $maxW / $w, $maxH / $h);
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        // flatten on white: no transparency surprises (signatures are dark ink on a transparent canvas)
        $flat = imagecreatetruecolor($tw, $th);
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopyresampled($flat, $img, 0, 0, $sx, $sy, $tw, $th, $w, $h);
        imagedestroy($img);

        ob_start();
        imagepng($flat);
        $png = (string) ob_get_clean();
        imagedestroy($flat);

        // handed to mPDF as an in-memory image (`var:`); a multi-hundred-KB base64 string inside the HTML can
        // exceed PCRE limits and be dropped silently
        $key = 'img'.count($this->imageVars);
        $this->imageVars[$key] = $png;
        $this->imageSize['var:'.$key] = [$tw, $th];

        return 'var:'.$key;
    }

    private function logoPath(): string
    {
        $logo = company_setting()?->logo;
        $file = $logo ? public_path($logo) : null;

        return str_replace('\\', '/', $file && is_file($file) ? $file : public_path('asset/shukran.png'));
    }
}

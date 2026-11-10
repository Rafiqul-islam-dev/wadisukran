// Static bilingual (EN / AR) labels for the Sales Point Registration & Agreement form.
// Kept in one place so the wording is not scattered across components.

export const L = {
    title: { en: 'SALES POINT REGISTRATION & AGREEMENT FORM', ar: 'استمارة تسجيل نقطة البيع والاتفاقية وادي شكران' },

    sections: {
        business: { en: 'BUSINESS INFORMATION', ar: '' },
        products: { en: 'PRODUCT INFORMATION', ar: 'معلومات المنتجات' },
        devices: { en: 'DEVICE & SERVICE ALLOCATION', ar: 'تخصيص الأجهزة والخدمات' },
        terms: { en: 'AGREEMENT TERMS', ar: 'شروط الاتفاقية' },
        documents: { en: 'ATTACH DOCUMENTS', ar: 'إرفق المستندات' },
        signatures: { en: 'SIGNATURES', ar: 'التوقيعات' },
    },

    shop_name: { en: 'Business Shop Name', ar: 'اسم المتجر التجاري' },
    owner_name: { en: 'Authorized Person / Owner Name', ar: 'اسم الشخص المفوض / المالك' },
    passport_no: { en: 'Passport Number', ar: 'رقم جواز السفر' },
    uid_no: { en: 'UID Number', ar: 'رقم الموحد' },
    eid_no: { en: 'E-ID Number', ar: 'رقم الهوية' },
    trade_license_no: { en: 'Trade License Number', ar: 'رقم الرخصة التجارية' },
    email: { en: 'Email Address', ar: 'عنوان البريد الإلكتروني' },
    mobile: { en: 'Mobile Number', ar: 'رقم الهاتف' },
    whatsapp: { en: 'Whatsapp Number', ar: 'رقم واتساب' },
    address: { en: 'Full Business Address', ar: 'العنوان' },
    location_map: { en: 'Location Map', ar: 'خريطة الموقع' },
    photo: { en: 'Authorized Person Photo', ar: 'صورة الشخص المفوض' },

    products: {
        chocolate: { en: 'Chocolate', ar: 'شوكولاتة' },
        attar: { en: 'Attar', ar: 'عطر' },
        tasbih: { en: 'Tasbih', ar: 'مسبحة' },
        pen: { en: 'Pen', ar: 'قلم' },
        others: { en: 'Others', ar: 'أخرى' },
    },

    devices: {
        dev_tab: { en: 'Tab', ar: 'جهاز لوحي' },
        dev_pos_machine: { en: 'POS Machine', ar: 'جهاز نقط البيع' },
        dev_sim_card: { en: 'SIM Card', ar: 'شريحة الاتصال' },
        dev_active_data: { en: 'Active Data', ar: 'بيانات مفعلة' },
    },
    sim_card_provided: { en: 'SIM Card Provided', ar: 'تم توفير شريحة' },
    delivery_date: { en: 'Date of Delivery', ar: 'تاريخ التسليم' },
    delivery_time: { en: 'Time of Delivery', ar: 'وقت التسليم' },

    documents: {
        trade_license: { en: 'Trade License Copy', ar: 'نسخة الرخصة التجارية' },
        passport: { en: 'Passport Copy', ar: 'نسخة جواز السفر' },
        visa: { en: 'Visa Copy', ar: 'نسخة التأشيرة أو الإقامة' },
        emirates_id: { en: 'Emirates ID Copy', ar: 'نسخة الهوية (الوجه الأمامي)' },
        business_card: { en: 'Business Card', ar: 'بطاقة العمل' },
        shop_picture: { en: 'Shop Picture', ar: 'صورة المتجر' },
    } as Record<string, { en: string; ar: string }>,

    person_signature: { en: 'Authorized Person / Sales Point Signature', ar: 'توقيع الشخص المفوض / نقط البيع' },
    company_signature: { en: 'Company Representative Signature', ar: 'توقيع مندوب الشركة' },
    signed_date: { en: 'Date', ar: 'التاريخ' },
    signed_place: { en: 'Place', ar: 'الموقع' },
};

export const inputClass =
    'h-[38px] w-full rounded-md border-[1.6px] border-[#243475] bg-white px-3 text-sm text-[#1d1d1b] placeholder:text-[#9d9d9c] focus:outline-none focus:ring-2 focus:ring-[#e6007e]/40 disabled:bg-gray-50';

export const PINK = '#e6007e';
export const NAVY = '#243475';

export const statusStyles: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
};

export interface RegistrationFormData {
    shop_name: string;
    owner_name: string;
    passport_no: string;
    uid_no: string;
    eid_no: string;
    trade_license_no: string;
    email: string;
    mobile_country_code: string;
    mobile: string;
    whatsapp_country_code: string;
    whatsapp: string;
    address: string;
    location_map: string;
    photo: File | null;
    products: { chocolate: boolean; attar: boolean; tasbih: boolean; pen: boolean; others: boolean };
    products_other_text: string;
    dev_tab: boolean;
    dev_pos_machine: boolean;
    dev_sim_card: boolean;
    dev_active_data: boolean;
    sim_card_provided: boolean;
    delivery_date: string;
    delivery_time: string;
    person_signature: string;
    company_signature: string;
    signed_date: string;
    signed_place: string;
    documents: Record<string, File | null>;
}

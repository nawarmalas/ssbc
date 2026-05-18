<?php

namespace Database\Seeders;

use App\Models\FormField;
use App\Models\FormSection;
use App\Services\FormService;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run(): void
    {
        if (FormSection::where('form_id', 'join-us')->exists()) {
            return;
        }

        // ── Section 1: Personal Information ─────────────────────────────────
        $personal = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Personal Information',
            'title_ar' => 'المعلومات الشخصية',
            'is_repeatable' => false,
            'order_index' => 0,
        ]);

        $personalFields = [
            ['label_en' => 'Full Name in Arabic (as in Passport)', 'label_ar' => 'الاسم الكامل بالعربية (كما في جواز السفر)', 'field_type' => 'text', 'is_required' => true, 'placeholder_en' => '', 'placeholder_ar' => ''],
            ['label_en' => 'Full Name in English (as in Passport)', 'label_ar' => 'الاسم الكامل بالإنجليزية (كما في جواز السفر)', 'field_type' => 'text', 'is_required' => true, 'placeholder_en' => '', 'placeholder_ar' => ''],
            ['label_en' => 'Date of Birth', 'label_ar' => 'تاريخ الميلاد', 'field_type' => 'date', 'is_required' => true, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'Current Position', 'label_ar' => 'المسمى الوظيفي الحالي', 'field_type' => 'text', 'is_required' => true, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'Mobile Number with Country Code', 'label_ar' => 'رقم الجوال مع رمز الدولة', 'field_type' => 'tel', 'is_required' => true, 'placeholder_en' => '+966 50 123 4567', 'placeholder_ar' => '+966 50 123 4567'],
            ['label_en' => 'Email Address', 'label_ar' => 'البريد الإلكتروني', 'field_type' => 'email', 'is_required' => true, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'Home Address', 'label_ar' => 'العنوان السكني', 'field_type' => 'textarea', 'is_required' => false, 'placeholder_en' => null, 'placeholder_ar' => null],
            ['label_en' => 'LinkedIn Profile Link', 'label_ar' => 'رابط الملف الشخصي على لينكد إن', 'field_type' => 'url', 'is_required' => false, 'placeholder_en' => 'https://linkedin.com/in/yourprofile', 'placeholder_ar' => null],
        ];

        foreach ($personalFields as $i => $field) {
            FormField::create(array_merge($field, ['section_id' => $personal->id, 'order_index' => $i, 'is_active' => true]));
        }

        // ── Section 2: Company Information (repeatable) ──────────────────────
        $sectors = [
            ['label_en' => 'Agriculture and Livestock', 'label_ar' => 'الزراعة والثروة الحيوانية', 'value' => 'agriculture'],
            ['label_en' => 'Oil and Mineral Resources', 'label_ar' => 'النفط والموارد المعدنية', 'value' => 'oil'],
            ['label_en' => 'Electricity and Water', 'label_ar' => 'الكهرباء والمياه', 'value' => 'electricity'],
            ['label_en' => 'Health and Pharmaceuticals', 'label_ar' => 'الصحة والدواء', 'value' => 'health'],
            ['label_en' => 'Real Estate Development and Construction', 'label_ar' => 'التطوير العقاري والإنشاء', 'value' => 'realestate'],
            ['label_en' => 'Education and Training', 'label_ar' => 'التعليم والتدريب', 'value' => 'education'],
            ['label_en' => 'Tourism', 'label_ar' => 'السياحة', 'value' => 'tourism'],
            ['label_en' => 'Drama and Media', 'label_ar' => 'الدراما والإعلام', 'value' => 'media'],
            ['label_en' => 'Development Work', 'label_ar' => 'العمل التنموي', 'value' => 'development'],
            ['label_en' => 'Transport and Logistics', 'label_ar' => 'النقل واللوجستيات', 'value' => 'transport'],
            ['label_en' => 'Telecommunications / IT / Business Incubators', 'label_ar' => 'الاتصالات وتقنية المعلومات وحاضنات الأعمال', 'value' => 'telecom'],
            ['label_en' => 'Legal Consulting and Services', 'label_ar' => 'الاستشارات والخدمات القانونية', 'value' => 'legal'],
        ];

        $sizeOptions = [
            ['label_en' => '1–10 employees', 'label_ar' => '1–10 موظفين', 'value' => '1-10'],
            ['label_en' => '11–50 employees', 'label_ar' => '11–50 موظفاً', 'value' => '11-50'],
            ['label_en' => '51–200 employees', 'label_ar' => '51–200 موظف', 'value' => '51-200'],
            ['label_en' => '200+ employees', 'label_ar' => 'أكثر من 200 موظف', 'value' => '200+'],
        ];

        $countryOptions = [
            ['label_en' => 'Syria', 'label_ar' => 'سوريا', 'value' => 'syria'],
            ['label_en' => 'KSA', 'label_ar' => 'المملكة العربية السعودية', 'value' => 'ksa'],
            ['label_en' => 'Both', 'label_ar' => 'كلاهما', 'value' => 'both'],
            ['label_en' => 'Other', 'label_ar' => 'أخرى', 'value' => 'other'],
        ];

        $company = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Company Information',
            'title_ar' => 'معلومات الشركة',
            'is_repeatable' => true,
            'max_repeats' => 5,
            'order_index' => 1,
        ]);

        $companyFields = [
            ['label_en' => 'Company Name', 'label_ar' => 'اسم الشركة', 'field_type' => 'text', 'is_required' => true, 'options' => null, 'validation_rules' => null],
            ['label_en' => 'Company Establishment Year', 'label_ar' => 'سنة تأسيس الشركة', 'field_type' => 'number', 'is_required' => true, 'options' => null, 'validation_rules' => ['min' => 1900, 'max' => (int) date('Y')]],
            ['label_en' => 'Company Size', 'label_ar' => 'حجم الشركة', 'field_type' => 'radio', 'is_required' => true, 'options' => $sizeOptions, 'validation_rules' => null],
            ['label_en' => 'Business Address', 'label_ar' => 'عنوان الشركة', 'field_type' => 'textarea', 'is_required' => true, 'options' => null, 'validation_rules' => null],
            ['label_en' => 'Company Website', 'label_ar' => 'الموقع الإلكتروني للشركة', 'field_type' => 'url', 'is_required' => false, 'options' => null, 'validation_rules' => null],
            ['label_en' => 'Current Operations Country', 'label_ar' => 'بلد العمليات الحالية', 'field_type' => 'radio', 'is_required' => false, 'options' => $countryOptions, 'validation_rules' => null],
            ['label_en' => 'Sectors of Operation', 'label_ar' => 'قطاعات العمل', 'field_type' => 'checkbox_group', 'is_required' => true, 'options' => $sectors, 'validation_rules' => null],
        ];

        foreach ($companyFields as $i => $field) {
            FormField::create(array_merge($field, [
                'section_id' => $company->id,
                'order_index' => $i,
                'is_active' => true,
                'placeholder_en' => null,
                'placeholder_ar' => null,
                'file_config' => null,
            ]));
        }

        // ── Section 3: Required Documents ───────────────────────────────────
        $docs = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Required Documents',
            'title_ar' => 'المستندات المطلوبة',
            'is_repeatable' => false,
            'order_index' => 2,
        ]);

        $docFields = [
            [
                'label_en' => 'Copy of ID or Passport',
                'label_ar' => 'نسخة من الهوية أو جواز السفر',
                'field_type' => 'file',
                'is_required' => true,
                'file_config' => ['accepted_types' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_size_mb' => 5],
            ],
            [
                'label_en' => 'Commercial Registry or Trade License',
                'label_ar' => 'السجل التجاري أو الرخصة التجارية',
                'field_type' => 'file',
                'is_required' => true,
                'file_config' => ['accepted_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'], 'max_size_mb' => 5],
            ],
            [
                'label_en' => 'Company Profile',
                'label_ar' => 'ملف تعريف الشركة',
                'field_type' => 'file',
                'is_required' => false,
                'file_config' => ['accepted_types' => ['pdf', 'doc', 'docx'], 'max_size_mb' => 5],
            ],
        ];

        foreach ($docFields as $i => $field) {
            FormField::create(array_merge($field, [
                'section_id' => $docs->id,
                'order_index' => $i,
                'is_active' => true,
                'placeholder_en' => null,
                'placeholder_ar' => null,
                'options' => null,
                'validation_rules' => null,
            ]));
        }

        // ── Section 4: Declaration ───────────────────────────────────────────
        $decl = FormSection::create([
            'form_id' => 'join-us',
            'title_en' => 'Declaration',
            'title_ar' => 'الإقرار',
            'is_repeatable' => false,
            'order_index' => 3,
        ]);

        FormField::create([
            'section_id' => $decl->id,
            'label_en' => 'I declare that all provided information and attached documents are true and accurate, and I consent to sharing them with the SSBC committees for evaluation.',
            'label_ar' => 'أُقرّ بأن جميع المعلومات والمستندات المقدمة صحيحة ودقيقة، وأوافق على مشاركتها مع لجان المجلس للتقييم.',
            'field_type' => 'declaration',
            'is_required' => true,
            'is_active' => true,
            'order_index' => 0,
            'placeholder_en' => null,
            'placeholder_ar' => null,
            'options' => null,
            'validation_rules' => null,
            'file_config' => null,
        ]);

        FormService::invalidateCache('join-us');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            [
                'sort_order'     => 1,
                'name_ar'        => 'القطاع الزراعي والثروة الحيوانية',
                'name_en'        => 'Agriculture and Livestock',
                'description_ar' => 'تعزيز الاستثمار في سلاسل القيمة الزراعية والإنتاج الحيواني لرفع كفاءة الإنتاج، وتحقيق الأمن الغذائي، وتوسيع القدرات التصديرية.',
                'description_en' => 'Strengthening investment in agricultural value chains and livestock production to boost efficiency, achieve food security, and expand export capacity.',
            ],
            [
                'sort_order'     => 2,
                'name_ar'        => 'قطاع المال والمصارف والتأمين',
                'name_en'        => 'Finance Banking and Insurance',
                'description_ar' => 'تطوير الخدمات المالية والمصرفية والتأمينية لتمكين الاستثمار، وتسهيل التمويل، وتعزيز الاستقرار الاقتصادي.',
                'description_en' => 'Developing financial, banking, and insurance services to enable investment, facilitate financing, and strengthen economic stability.',
            ],
            [
                'sort_order'     => 3,
                'name_ar'        => 'قطاع الصناعة',
                'name_en'        => 'Industry and Manufacturing',
                'description_ar' => 'تطوير الصناعات التحويلية والمشاريع الصناعية ذات القيمة المضافة، بما يدعم إعادة البناء ويعزز التكامل مع سلاسل التوريد الإقليمية.',
                'description_en' => 'Developing manufacturing industries and value-added industrial projects to support reconstruction and strengthen integration with regional supply chains.',
            ],
            [
                'sort_order'     => 4,
                'name_ar'        => 'قطاع الصادرات البينية والتجارة',
                'name_en'        => 'Intraregional Trade and Exports',
                'description_ar' => 'تعزيز التبادل التجاري وتنشيط الصادرات من سوريا إلى المملكة والأسواق الإقليمية، عبر تطوير سلاسل التوريد وتحسين جودة المنتجات وتسهيل النفاذ إلى الأسواق.',
                'description_en' => 'Boosting trade exchange and activating Syrian exports to the Kingdom and regional markets through supply chain development, product quality improvement, and market access facilitation.',
            ],
            [
                'sort_order'     => 5,
                'name_ar'        => 'قطاع النفط والثروة المعدنية',
                'name_en'        => 'Oil and Mineral Resources',
                'description_ar' => 'دعم الاستثمار في الطاقة التقليدية والمتجددة، وتطوير البنية التحتية للإنتاج والتوزيع بما يعزز الكفاءة والاستدامة.',
                'description_en' => 'Supporting investment in traditional and renewable energy, and developing production and distribution infrastructure to enhance efficiency and sustainability.',
            ],
            [
                'sort_order'     => 6,
                'name_ar'        => 'قطاع الكهرباء والمياه',
                'name_en'        => 'Electricity and Water',
                'description_ar' => 'تطوير البنية التحتية للكهرباء والمياه وفق نماذج استثمارية مستدامة تدعم إعادة الإعمار والتنمية.',
                'description_en' => 'Developing electricity and water infrastructure through sustainable investment models that support reconstruction and development.',
            ],
            [
                'sort_order'     => 7,
                'name_ar'        => 'قطاع الصحة',
                'name_en'        => 'Health and Pharmaceuticals',
                'description_ar' => 'تعزيز الاستثمار في الرعاية الصحية والخدمات الطبية والصناعات الدوائية بما يرفع جودة الخدمات ويخلق فرصا اقتصادية عالية القيمة.',
                'description_en' => 'Boosting investment in healthcare, medical services, and pharmaceutical industries to raise service quality and create high-value economic opportunities.',
            ],
            [
                'sort_order'     => 8,
                'name_ar'        => 'قطاع الإنشاء والتطوير العقاري',
                'name_en'        => 'Real Estate Development and Construction',
                'description_ar' => 'تطوير مشاريع البنية التحتية والعقار وفق نماذج استثمارية مستدامة تدعم إعادة الإعمار والتنمية الحضرية.',
                'description_en' => 'Developing infrastructure and real estate projects through sustainable investment models supporting reconstruction and urban development.',
            ],
            [
                'sort_order'     => 9,
                'name_ar'        => 'قطاع التعليم والتدريب',
                'name_en'        => 'Education and Training',
                'description_ar' => 'تطوير برامج التعليم والتأهيل المهني لرفع كفاءة رأس المال البشري ومواءمة المهارات مع احتياجات السوق والاستثمار.',
                'description_en' => 'Developing education and vocational training programs to enhance human capital and align skills with market and investment needs.',
            ],
            [
                'sort_order'     => 10,
                'name_ar'        => 'قطاع السياحة',
                'name_en'        => 'Tourism',
                'description_ar' => 'تنمية السياحة الثقافية والدينية والترفيهية والطبية، وتطوير مشاريع الضيافة بما يعزز الجاذبية الاستثمارية ويخلق قيمة اقتصادية مستدامة.',
                'description_en' => 'Developing cultural, religious, recreational, and medical tourism, and building hospitality projects to boost investment attractiveness and create sustainable economic value.',
            ],
            [
                'sort_order'     => 11,
                'name_ar'        => 'قطاع الدراما والميديا',
                'name_en'        => 'Drama and Media',
                'description_ar' => 'دعم الاستثمار في الإنتاج الإعلامي والمحتوى الإبداعي والمنصات الرقمية كجزء من اقتصاد المعرفة وتعزيز الحضور الثقافي والاقتصادي.',
                'description_en' => 'Supporting investment in media production, creative content, and digital platforms as part of the knowledge economy and strengthening cultural and economic presence.',
            ],
            [
                'sort_order'     => 12,
                'name_ar'        => 'قطاع العمل التنموي',
                'name_en'        => 'Development Work',
                'description_ar' => 'تعزيز المبادرات التنموية ذات الأثر الاقتصادي والاجتماعي، وبناء شراكات تدعم الاستقرار والتنمية المستدامة.',
                'description_en' => 'Strengthening development initiatives with economic and social impact, and building partnerships that support stability and sustainable development.',
            ],
            [
                'sort_order'     => 13,
                'name_ar'        => 'قطاع النقل والخدمات اللوجستية',
                'name_en'        => 'Transport and Logistics',
                'description_ar' => 'تعزيز كفاءة سلاسل الإمداد وتطوير البنية اللوجستية وربط مناطق الإنتاج بالمرافئ والأسواق الإقليمية.',
                'description_en' => 'Enhancing supply chain efficiency and developing logistics infrastructure connecting production zones to ports and regional markets.',
            ],
            [
                'sort_order'     => 14,
                'name_ar'        => 'قطاع الاتصالات وتقنية المعلومات وحاضنات ومسرعات الأعمال',
                'name_en'        => 'Telecommunications IT and Business Incubators',
                'description_ar' => 'تطوير البنية الرقمية، ودعم الابتكار وريادة الأعمال عبر حلول تقنية حديثة ومنصات نمو للشركات الناشئة.',
                'description_en' => 'Developing digital infrastructure and supporting innovation and entrepreneurship through modern technology solutions and growth platforms for startups.',
            ],
            [
                'sort_order'     => 15,
                'name_ar'        => 'قطاع الخدمات والاستشارات',
                'name_en'        => 'Services and Consulting',
                'description_ar' => 'دعم البيئة القانونية والتنظيمية للاستثمار عبر تقديم خدمات استشارية متخصصة تضمن الامتثال وتعزز الثقة في بيئة الأعمال.',
                'description_en' => 'Supporting the legal and regulatory investment environment through specialized consulting services that ensure compliance and build business confidence.',
            ],
        ];

        foreach ($sectors as $sector) {
            Sector::updateOrCreate(
                ['name_en' => $sector['name_en']],
                $sector + ['is_active' => true]
            );
        }
    }
}

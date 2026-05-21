<?php

namespace Tests\Feature;

use App\Models\FormField;
use App\Models\FormSection;
use App\Models\Sector;
use App\Services\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SectorObserverTest extends TestCase
{
    use RefreshDatabase;

    private function makeSection(): FormSection
    {
        return FormSection::create([
            'form_id'       => 'join-us',
            'title_en'      => 'Company',
            'title_ar'      => 'شركة',
            'is_repeatable' => false,
            'order_index'   => 0,
        ]);
    }

    private function makeSectorsField(FormSection $section): FormField
    {
        return FormField::create([
            'section_id'        => $section->id,
            'code'              => 'sectors_of_operation',
            'label_en'          => 'Sectors of Operation',
            'label_ar'          => 'قطاعات العمل',
            'field_type'        => 'checkbox_group',
            'is_required'       => true,
            'is_active'         => true,
            'is_system_managed' => true,
            'options_source'    => 'sectors',
            'order_index'       => 0,
            'options'           => [],
        ]);
    }

    public function test_creating_sector_rebuilds_form_field_options(): void
    {
        $section = $this->makeSection();
        $field   = $this->makeSectorsField($section);

        Sector::create([
            'name_en'        => 'Agriculture',
            'name_ar'        => 'الزراعة',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'sort_order'     => 1,
            'is_active'      => true,
        ]);

        $field->refresh();
        $this->assertCount(1, $field->options);
        $this->assertSame('agriculture', $field->options[0]['value']);
        $this->assertSame('Agriculture', $field->options[0]['label_en']);
    }

    public function test_updating_sector_name_rebuilds_options(): void
    {
        $section = $this->makeSection();
        $field   = $this->makeSectorsField($section);

        $sector = Sector::create([
            'name_en'        => 'Agriculture',
            'name_ar'        => 'الزراعة',
            'description_en' => 'Desc',
            'description_ar' => 'وصف',
            'sort_order'     => 1,
            'is_active'      => true,
        ]);

        $sector->update(['name_en' => 'Updated Agriculture', 'name_ar' => 'زراعة محدثة']);

        $field->refresh();
        $this->assertSame('Updated Agriculture', $field->options[0]['label_en']);
        $this->assertSame('زراعة محدثة', $field->options[0]['label_ar']);
    }

    public function test_soft_deleting_sector_removes_it_from_options(): void
    {
        $section = $this->makeSection();
        $field   = $this->makeSectorsField($section);

        $s1 = Sector::create([
            'name_en' => 'Agriculture', 'name_ar' => 'الزراعة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 1, 'is_active' => true,
        ]);
        $s2 = Sector::create([
            'name_en' => 'Tourism', 'name_ar' => 'السياحة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 2, 'is_active' => true,
        ]);

        $s1->delete(); // soft delete

        $field->refresh();
        $this->assertCount(1, $field->options);
        $this->assertSame('tourism', $field->options[0]['value']);
    }

    public function test_observer_syncs_all_sectors_backed_fields(): void
    {
        $section     = $this->makeSection();
        $systemField = $this->makeSectorsField($section);

        // A second, non-system field that opts into the sectors option source.
        $section2 = FormSection::create([
            'form_id'       => 'join-us',
            'title_en'      => 'Other',
            'title_ar'      => 'أخرى',
            'is_repeatable' => false,
            'order_index'   => 1,
        ]);
        $customField = FormField::create([
            'section_id'        => $section2->id,
            'label_en'          => 'Sectors of Interest',
            'label_ar'          => 'قطاعات الاهتمام',
            'field_type'        => 'checkbox_group',
            'is_required'       => false,
            'is_active'         => true,
            'is_system_managed' => false,
            'options_source'    => 'sectors',
            'order_index'       => 0,
            'options'           => [],
        ]);

        Sector::create([
            'name_en' => 'Agriculture', 'name_ar' => 'الزراعة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $systemField->refresh();
        $customField->refresh();

        $this->assertCount(1, $systemField->options);
        $this->assertCount(1, $customField->options);
        $this->assertSame('agriculture', $customField->options[0]['value']);
    }

    public function test_observer_invalidates_form_cache(): void
    {
        $section = $this->makeSection();
        $this->makeSectorsField($section);

        // Prime the cache
        FormService::getActiveForm('join-us');
        $this->assertTrue(Cache::has('form:join-us:sections'));

        Sector::create([
            'name_en' => 'Agriculture', 'name_ar' => 'الزراعة',
            'description_en' => 'D', 'description_ar' => 'د',
            'sort_order' => 1, 'is_active' => true,
        ]);

        $this->assertFalse(Cache::has('form:join-us:sections'));
    }
}

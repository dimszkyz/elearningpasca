<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Contains renderers for the course management pages.
 *
 * @package core_course
 * @copyright 2013 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/renderer.php');

/**
 * Main renderer for the course management pages.
 *
 * This renderer keeps the standard course/category management page usable and
 * adds the Pasca Prodi sync action beside the native Create new category button.
 *
 * @package core_course
 * @copyright 2013 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_course_management_renderer extends plugin_renderer_base {

    /**
     * Initialises the JS required to enhance the management interface.
     */
    public function enhance_management_interface() {
        $this->page->requires->yui_module('moodle-course-management', 'M.course.management.init');
        $this->page->requires->strings_for_js(
            [
                'show', 'showcategory', 'hide', 'expand', 'expandcategory',
                'collapse', 'collapsecategory', 'confirmcoursemove', 'move', 'cancel', 'confirm'
            ],
            'moodle'
        );
    }

    /**
     * Render tertiary action bar.
     */
    public function render_action_bar($actionbar) {
        $data = $actionbar->export_for_template($this);
        $html = '';
        if (!empty($data['heading'])) {
            $html .= html_writer::tag('h2', $data['heading'], ['class' => 'h3 mb-3']);
        }
        if (!empty($data['backbutton'])) {
            $html .= html_writer::div($this->render_from_template('core/single_button', $data['backbutton']), 'mb-3');
        }
        return $html;
    }

    /**
     * Prepares the form element for the course category listing bulk actions.
     */
    public function management_form_start() {
        $form = ['action' => $this->page->url->out(), 'method' => 'POST', 'id' => 'coursecat-management'];
        $html = html_writer::start_tag('form', $form);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'bulkaction']);
        return $html;
    }

    /**
     * Closes the course category bulk management form.
     */
    public function management_form_end() {
        return html_writer::end_tag('form');
    }

    /**
     * Skip-to links placeholder.
     */
    public function accessible_skipto_links($displaycategorylisting, $displaycourselisting, $displaycoursedetail) {
        return '';
    }

    /**
     * Start responsive grid.
     */
    public function grid_start($id, $class = '') {
        return html_writer::start_div('row ' . $class, ['id' => $id]);
    }

    /**
     * End responsive grid.
     */
    public function grid_end() {
        return html_writer::end_div();
    }

    /**
     * Start grid column.
     */
    public function grid_column_start($size, $class = '') {
        $size = max(1, min(12, (int) $size));
        return html_writer::start_div('col-md-' . $size . ' ' . $class);
    }

    /**
     * End grid column.
     */
    public function grid_column_end() {
        return html_writer::end_div();
    }

    /**
     * Presents a course category listing.
     */
    public function category_listing(?core_course_category $category = null) {
        $selectedcategory = $category ? (int) $category->id : null;
        $listing = core_course_category::top()->get_children();

        $html = html_writer::start_div('category-listing card w-100');
        $html .= html_writer::tag('h3', get_string('categories'), [
            'class' => 'card-header',
            'id' => 'category-listing-title',
        ]);
        $html .= html_writer::start_div('card-body');
        $html .= $this->category_listing_actions($category);
        $html .= html_writer::start_tag('ul', [
            'class' => 'ms-1 list-unstyled category-list list-group',
            'role' => 'tree',
            'aria-labelledby' => 'category-listing-title',
        ]);

        foreach ($listing as $listitem) {
            $html .= $this->category_listitem($listitem, $listitem->get_children(), $listitem->get_children_count(),
                $selectedcategory, []);
        }

        $html .= html_writer::end_tag('ul');
        $html .= $this->category_bulk_actions($category);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render a category list item recursively.
     */
    public function category_listitem(core_course_category $category, array $subcategories, $totalsubcategories,
            $selectedcategory = null, $selectedcategories = []) {
        $activecategory = ((int) $selectedcategory === (int) $category->id);
        $text = $category->get_formatted_name();
        $viewcaturl = new moodle_url('/course/management.php', ['categoryid' => $category->id]);
        $actions = \core_course\management\helper::get_category_listitem_actions($category);

        $attributes = [
            'class' => 'listitem listitem-category list-group-item list-group-item-action',
            'data-id' => $category->id,
            'data-selected' => $activecategory ? '1' : '0',
            'data-visible' => $category->visible ? '1' : '0',
            'role' => 'treeitem',
        ];

        $html = html_writer::start_tag('li', $attributes);
        $html .= html_writer::start_div('clearfix');
        $html .= html_writer::link($viewcaturl, $text, ['class' => 'float-start categoryname aalink']);
        $html .= html_writer::start_div('float-end d-flex align-items-center gap-1');
        if ($category->idnumber) {
            $html .= html_writer::tag('span', s($category->idnumber), ['class' => 'text-muted idnumber']);
        }
        $html .= html_writer::span((string) $category->get_courses_count(), 'course-count text-muted');
        $html .= $this->category_listitem_actions($category, $actions);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        if (!empty($subcategories)) {
            $html .= html_writer::start_tag('ul', ['class' => 'ml list-unstyled', 'role' => 'group']);
            foreach ($subcategories as $subcategory) {
                $html .= $this->category_listitem($subcategory, $subcategory->get_children(),
                    $subcategory->get_children_count(), $selectedcategory, []);
            }
            $html .= html_writer::end_tag('ul');
        }

        $html .= html_writer::end_tag('li');
        return $html;
    }

    /**
     * Renders the actions that are possible for the course category listing.
     */
    public function category_listing_actions(?core_course_category $category = null) {
        $actions = [];

        $cancreatecategory = $category && $category->can_create_subcategory();
        $cancreatecategory = $cancreatecategory || core_course_category::can_create_top_level_category();
        if ($category === null) {
            $category = core_course_category::top();
        }

        if ($cancreatecategory) {
            $url = new moodle_url('/course/editcategory.php', ['parent' => $category->id]);
            $actions[] = html_writer::link($url, get_string('createnewcategory'), ['class' => 'btn btn-secondary']);
        }
        if (class_exists('\\local_pascaprodi\\manager') && has_capability('moodle/category:manage', context_system::instance())) {
            $url = new moodle_url('/local/pascaprodi/sync_categories.php');
            $actions[] = html_writer::link($url, get_string('synccategoriesbutton', 'local_pascaprodi'),
                ['class' => 'btn btn-secondary']);
        }
        if (core_course_category::can_approve_course_requests()) {
            $actions[] = html_writer::link(new moodle_url('/course/pending.php'), get_string('coursespending'),
                ['class' => 'btn btn-secondary']);
        }
        if (!$actions) {
            return '';
        }
        return html_writer::div(join(' ', $actions), 'listing-actions category-listing-actions mb-3');
    }

    /**
     * Renders the actions for individual category list items.
     */
    public function category_listitem_actions(core_course_category $category, ?array $actions = null) {
        if ($actions === null) {
            $actions = \core_course\management\helper::get_category_listitem_actions($category);
        }
        if (!$actions) {
            return '';
        }

        $menu = new action_menu();
        $label = get_string('actionsmenu');
        $actionicon = $this->output->pix_icon('t/edit_menu', '') . html_writer::span($label, 'visually-hidden');
        $menu->set_menu_trigger($actionicon, 'iconsmall actionmenu');
        $menu->triggerattributes['title'] = $label;
        $menu->attributes['class'] .= ' category-item-actions item-actions';
        $menu->attributes['role'] = 'menubar';

        foreach ($actions as $key => $action) {
            $menu->add(new action_menu_link(
                $action['url'],
                $action['icon'],
                $action['string'],
                in_array($key, ['show', 'hide', 'moveup', 'movedown']),
                ['data-action' => $key, 'class' => 'action-'.$key]
            ));
        }

        return $this->render($menu);
    }

    /**
     * Renders bulk actions for categories.
     */
    public function category_bulk_actions(?core_course_category $category = null) {
        return '';
    }

    /**
     * Renders course listing for a selected category.
     */
    public function course_listing(?core_course_category $category = null, ?core_course_list_element $course = null,
            $page = 0, $perpage = 20, $viewmode = 'default') {
        if ($category === null) {
            return $this->output->notification(get_string('selectacategory'), 'info');
        }

        $html = html_writer::start_div('card course-listing w-100', ['data-category' => $category->id]);
        $html .= html_writer::tag('h3', $category->get_formatted_name(), [
            'id' => 'course-listing-title',
            'tabindex' => '0',
            'class' => 'card-header',
        ]);
        $html .= html_writer::start_div('card-body');
        $html .= $this->course_listing_actions($category, $course, $perpage);
        $html .= html_writer::start_tag('ul', ['class' => 'course-list list-group', 'role' => 'list']);

        $courses = $category->get_courses(['offset' => max(0, (int) $page) * max(2, (int) $perpage), 'limit' => max(2, (int) $perpage)]);
        foreach ($courses as $listitem) {
            $html .= $this->course_listitem($category, $listitem, $course ? $course->id : null);
        }

        $html .= html_writer::end_tag('ul');
        $html .= $this->course_bulk_actions($category);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render one course list item.
     */
    public function course_listitem(core_course_category $category, core_course_list_element $course, $selectedcourse = null) {
        $url = new moodle_url('/course/management.php', ['categoryid' => $category->id, 'courseid' => $course->id]);
        $classes = 'list-group-item';
        if ((int) $selectedcourse === (int) $course->id) {
            $classes .= ' active';
        }
        $name = format_string(get_course_display_name_for_list($course), true, ['context' => context_course::instance($course->id)]);
        return html_writer::tag('li', html_writer::link($url, $name), ['class' => $classes]);
    }

    /**
     * Renders course listing actions.
     */
    public function course_listing_actions(core_course_category $category, ?core_course_list_element $course = null, $perpage = 20) {
        $actions = [];
        if ($category->can_create_course()) {
            $actions[] = html_writer::link(new moodle_url('/course/edit.php', ['category' => $category->id]),
                get_string('createnewcourse'), ['class' => 'btn btn-secondary']);
        }
        return $actions ? html_writer::div(join(' ', $actions), 'listing-actions course-listing-actions mb-3') : '';
    }

    /**
     * Search listing.
     */
    public function search_listing($courses, $coursestotal, ?core_course_list_element $course = null, $page = 0,
            $perpage = 20, $search = '') {
        $html = html_writer::start_div('card course-listing w-100');
        $html .= html_writer::tag('h3', get_string('searchresults'), ['class' => 'card-header']);
        $html .= html_writer::start_div('card-body');
        $html .= html_writer::start_tag('ul', ['class' => 'course-list list-group', 'role' => 'list']);
        foreach ($courses as $listitem) {
            if (!$listitem instanceof core_course_list_element) {
                $listitem = new core_course_list_element($listitem);
            }
            $category = core_course_category::get($listitem->category, IGNORE_MISSING, true);
            if ($category) {
                $html .= $this->course_listitem($category, $listitem, $course ? $course->id : null);
            }
        }
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render course detail panel.
     */
    public function course_detail(core_course_list_element $course) {
        $context = context_course::instance($course->id);
        $name = format_string(get_course_display_name_for_list($course), true, ['context' => $context]);
        $html = html_writer::start_div('card course-detail w-100');
        $html .= html_writer::tag('h3', $name, ['class' => 'card-header']);
        $html .= html_writer::start_div('card-body');
        $html .= html_writer::div(html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), get_string('view')), 'mb-2');
        if (has_capability('moodle/course:update', $context)) {
            $html .= html_writer::div(html_writer::link(new moodle_url('/course/edit.php', ['id' => $course->id]), get_string('edit')), 'mb-2');
        }
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Course bulk actions placeholder.
     */
    public function course_bulk_actions(core_course_category $category) {
        return '';
    }

    /**
     * Detail pair helper.
     */
    public function detail_pair($key, $value) {
        return html_writer::div(
            html_writer::div($key, 'pair-key col-md-3') . html_writer::div($value, 'pair-value col-md-9'),
            'detail-pair row my-1'
        );
    }

    /**
     * Action menu render helper.
     */
    public function render_action_menu($menu) {
        return $this->output->render($menu);
    }
}

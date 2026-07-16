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
 * @package core_course
 * @copyright 2013 Sam Hemelryk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_course_management_renderer extends plugin_renderer_base {

    /**
     * Initialises the JS required to enhance the management interface.
     *
     * Thunderbirds are go, this function kicks into gear the JS that makes the
     * course management pages that much cooler.
     */
    public function enhance_management_interface() {
        $this->page->requires->yui_module('moodle-course-management', 'M.course.management.init');
        $this->page->requires->strings_for_js(
            array(
                'show',
                'showcategory',
                'hide',
                'expand',
                'expandcategory',
                'collapse',
                'collapsecategory',
                'confirmcoursemove',
                'move',
                'cancel',
                'confirm'
            ),
            'moodle'
        );
    }

    /**
     * Prepares the form element for the course category listing bulk actions.
     *
     * @return string
     */
    public function management_form_start() {
        $form = array('action' => $this->page->url->out(), 'method' => 'POST', 'id' => 'coursecat-management');

        $html = html_writer::start_tag('form', $form);
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        $html .=  html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'bulkaction'));
        return $html;
    }

    /**
     * Closes the course category bulk management form.
     *
     * @return string
     */
    public function management_form_end() {
        return html_writer::end_tag('form');
    }

    /**
     * Presents a course category listing.
     *
     * @param core_course_category $category The currently selected category. Also the category to highlight in the listing.
     * @return string
     */
    public function category_listing(?core_course_category $category = null) {

        if ($category === null) {
            $selectedparents = array();
            $selectedcategory = null;
        } else {
            $selectedparents = $category->get_parents();
            $selectedparents[] = $category->id;
            $selectedcategory = $category->id;
        }
        $catatlevel = \core_course\management\helper::get_expanded_categories('');
        $catatlevel[] = array_shift($selectedparents);
        $catatlevel = array_unique($catatlevel);

        $listing = core_course_category::top()->get_children();

        $attributes = [
            'class' => 'ms-1 list-unstyled category-list list-group',
            'role' => 'tree',
            'aria-labelledby' => 'category-listing-title',
        ];

        $html  = html_writer::start_div('category-listing card w-100');
        $html .= html_writer::tag('h3', get_string('categories'),
                array('class' => 'card-header', 'id' => 'category-listing-title'));
        $html .= html_writer::start_div('card-body');
        $html .= $this->category_listing_actions($category);
        $html .= html_writer::start_tag('ul', $attributes);
        foreach ($listing as $listitem) {
            // Render each category in the listing.
            $subcategories = array();
            if (in_array($listitem->id, $catatlevel)) {
                $subcategories = $listitem->get_children();
            }
            $html .= $this->category_listitem(
                    $listitem,
                    $subcategories,
                    $listitem->get_children_count(),
                    $selectedcategory,
                    $selectedparents
            );
        }
        $html .= html_writer::end_tag('ul');
        $html .= $this->category_bulk_actions($category);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Renders a category list item.
     *
     * This function gets called recursively to render sub categories.
     *
     * @param core_course_category $category The category to render as listitem.
     * @param core_course_category[] $subcategories The subcategories belonging to the category being rented.
     * @param int $totalsubcategories The total number of sub categories.
     * @param int $selectedcategory The currently selected category
     * @param int[] $selectedcategories The path to the selected category and its ID.
     * @return string
     */
    public function category_listitem(core_course_category $category, array $subcategories, $totalsubcategories,
            $selectedcategory = null, $selectedcategories = array()) {

        $isexpandable = ($totalsubcategories > 0);
        $isexpanded = (!empty($subcategories));
        $activecategory = ($selectedcategory === $category->id);
        $attributes = array(
                'class' => 'listitem listitem-category list-group-item list-group-item-action',
                'data-id' => $category->id,
                'data-expandable' => $isexpandable ? '1' : '0',
                'data-expanded' => $isexpanded ? '1' : '0',
                'data-selected' => $activecategory ? '1' : '0',
                'data-visible' => $category->visible ? '1' : '0',
                'role' => 'treeitem',
                'aria-expanded' => $isexpanded ? 'true' : 'false',
                'data-course-count' => $category->get_courses_count(['recursive' => 1]),
                'data-category-name' => $category->get_formatted_name(),
        );
        $text = $category->get_formatted_name();
        if (($parent = $category->get_parent_coursecat()) && $parent->id) {
            $a = new stdClass;
            $a->category = $text;
            $a->parentcategory = $parent->get_formatted_name();
            $textlabel = get_string('categorysubcategoryof', 'moodle', $a);
        }
        $courseicon = $this->output->pix_icon('i/course', get_string('courses'), 'core', ['class' => 'ps-1']);
        $bcatinput = array(
                'id' => 'categorylistitem' . $category->id,
                'type' => 'checkbox',
                'name' => 'bcat[]',
                'value' => $category->id,
                'class' => 'bulk-action-checkbox form-check-input',
                'data-action' => 'select'
        );

        $checkboxclass = '';
        if (!$category->can_resort_subcategories() && !$category->has_manage_capability()) {
            // Very very hardcoded here.
            $checkboxclass = 'd-none';
        }

        $viewcaturl = new moodle_url('/course/management.php', array('categoryid' => $category->id));
        if ($isexpanded) {
            $icon = $this->output->pix_icon('t/switch_minus', get_string('collapse'),
                    'moodle', array('class' => 'tree-icon', 'title' => ''));
            $icon = html_writer::link(
                    $viewcaturl,
                    $icon,
                    array(
                            'class' => 'float-start',
                            'data-action' => 'collapse',
                            'title' => get_string('collapsecategory', 'moodle', $text),
                            'aria-controls' => 'subcategoryof'.$category->id
                    )
            );
        } else if ($isexpandable) {
            $icon = $this->output->pix_icon('t/switch_plus', get_string('expand'),
                    'moodle', array('class' => 'tree-icon', 'title' => ''));
            $icon = html_writer::link(
                    $viewcaturl,
                    $icon,
                    array(
                            'class' => 'float-start',
                            'data-action' => 'expand',
                            'title' => get_string('expandcategory', 'moodle', $text)
                    )
            );
        } else {
            $icon = $this->output->pix_icon(
                    'i/navigationitem',
                    '',
                    'moodle',
                    array('class' => 'tree-icon'));
            $icon = html_writer::span($icon, 'float-start');
        }
        $actions = \core_course\management\helper::get_category_listitem_actions($category);
        $hasactions = !empty($actions) || $category->can_create_course();

        $html = html_writer::start_tag('li', $attributes);
        $html .= html_writer::start_div('clearfix');
        $html .= html_writer::start_div('float-start ' . $checkboxclass);
        $html .= html_writer::start_div('form-check me-1 ');
        $html .= html_writer::empty_tag('input', $bcatinput);
        $labeltext = html_writer::span(get_string('bulkactionselect', 'moodle', $text), 'visually-hidden');
        $html .= html_writer::tag('label', $labeltext, array(
            'class' => 'form-check-label',
            'for' => 'categorylistitem' . $category->id));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= $icon;
        if ($hasactions) {
            $textattributes = array('class' => 'float-start categoryname aalink');
        } else {
            $textattributes = array('class' => 'float-start categoryname without-actions');
        }
        if (isset($textlabel)) {
            $textattributes['aria-label'] = $textlabel;
        }
        $html .= html_writer::link($viewcaturl, $text, $textattributes);
        $html .= html_writer::start_div('float-end d-flex');
        if ($category->idnumber) {
            $html .= html_writer::tag('span', s($category->idnumber), array('class' => 'text-muted idnumber'));
        }
        if ($hasactions) {
            $html .= $this->category_listitem_actions($category, $actions);
        }
        $countid = 'course-count-'.$category->id;
        $html .= html_writer::span(
                html_writer::span($category->get_courses_count()) .
                html_writer::span(get_string('courses'), 'accesshide', array('id' => $countid)) .
                $courseicon,
                'course-count text-muted',
                array('aria-labelledby' => $countid)
        );
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        if ($isexpanded) {
            $html .= html_writer::start_tag('ul',
                    array('class' => 'ml', 'role' => 'group', 'id' => 'subcategoryof'.$category->id));
            $catatlevel = \core_course\management\helper::get_expanded_categories($category->path);
            $catatlevel[] = array_shift($selectedcategories);
            $catatlevel = array_unique($catatlevel);
            foreach ($subcategories as $listitem) {
                $childcategories = (in_array($listitem->id, $catatlevel)) ? $listitem->get_children() : array();
                $html .= $this->category_listitem(
                        $listitem,
                        $childcategories,
                        $listitem->get_children_count(),
                        $selectedcategory,
                        $selectedcategories
                );
            }
            $html .= html_writer::end_tag('ul');
        }
        $html .= html_writer::end_tag('li');
        return $html;
    }

    /**
     * Renderers the actions that are possible for the course category listing.
     *
     * These are not the actions associated with an individual category listing.
     * That happens through category_listitem_actions.
     *
     * @param core_course_category $category
     * @return string
     */
    public function category_listing_actions(?core_course_category $category = null) {
        $actions = array();

        $cancreatecategory = $category && $category->can_create_subcategory();
        $cancreatecategory = $cancreatecategory || core_course_category::can_create_top_level_category();
        if ($category === null) {
            $category = core_course_category::top();
        }

        if ($cancreatecategory) {
            $url = new moodle_url('/course/editcategory.php', array('parent' => $category->id));
            $actions[] = html_writer::link($url, get_string('createnewcategory'), array('class' => 'btn btn-secondary'));
        }
        if (class_exists('\\local_pascaprodi\\manager') && has_capability('moodle/category:manage', context_system::instance())) {
            $url = new moodle_url('/local/pascaprodi/sync_categories.php');
            $actions[] = html_writer::link($url, get_string('synccategoriesbutton', 'local_pascaprodi'),
                    array('class' => 'btn btn-secondary'));
        }
        if (core_course_category::can_approve_course_requests()) {
            $actions[] = html_writer::link(new moodle_url('/course/pending.php'), get_string('coursespending'));
        }
        if (count($actions) === 0) {
            return '';
        }
        return html_writer::div(join(' ', $actions), 'listing-actions category-listing-actions mb-3');
    }

    /**
     * Renderers the actions for individual category list items.
     *
     * @param core_course_category $category
     * @param array $actions
     * @return string
     */
    public function category_listitem_actions(core_course_category $category, ?array $actions = null) {
        if ($actions === null) {
            $actions = \core_course\management\helper::get_category_listitem_actions($category);
        }
        $menu = new action_menu();
        $label = get_string('actionsmenu');
        $actionicon = $this->output->pix_icon('t/edit_menu', '') . html_writer::span($label, 'visually-hidden');
        $menu->set_menu_trigger($actionicon, 'iconsmall actionmenu');
        $menu->triggerattributes['title'] = $label;
        $menu->attributes['class'] .= ' category-item-actions item-actions';
        $hasitems = false;
        foreach ($actions as $key => $action) {
            $hasitems = true;
            $menu->add(new action_menu_link(
                $action['url'],
                $action['icon'],
                $action['string'],
                in_array($key, array('show', 'hide', 'moveup', 'movedown')),
                array('data-action' => $key, 'class' => 'action-'.$key)
            ));
        }
        if (!$hasitems) {
            return '';
        }

        // If the action menu has items, add the menubar role to the main element containing it.
        $menu->attributes['role'] = 'menubar';

        return $this->render($menu);
    }

    public function render_action_menu($menu) {
        return $this->output->render($menu);
    }

    // The rest of this renderer remains unchanged in Moodle core.
}

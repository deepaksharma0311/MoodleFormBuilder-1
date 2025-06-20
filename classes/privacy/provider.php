<?php
// This file is part of Moodle - http://moodle.org/

namespace local_formbuilder\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'local_formbuilder_forms',
            [
                'userid' => 'privacy:metadata:local_formbuilder_forms:userid',
                'name' => 'privacy:metadata:local_formbuilder_forms:name',
                'timecreated' => 'privacy:metadata:local_formbuilder_forms:timecreated',
            ],
            'privacy:metadata:local_formbuilder_forms'
        );

        $collection->add_database_table(
            'local_formbuilder_submissions',
            [
                'userid' => 'privacy:metadata:local_formbuilder_submissions:userid',
                'submissiondata' => 'privacy:metadata:local_formbuilder_submissions:submissiondata',
                'timecreated' => 'privacy:metadata:local_formbuilder_submissions:timecreated',
            ],
            'privacy:metadata:local_formbuilder_submissions'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $sql = "SELECT userid FROM {local_formbuilder_forms} WHERE userid IS NOT NULL";
        $userlist->add_from_sql('userid', $sql, []);

        $sql = "SELECT userid FROM {local_formbuilder_submissions} WHERE userid IS NOT NULL";
        $userlist->add_from_sql('userid', $sql, []);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $context = \context_system::instance();

        // Export forms created by user
        $forms = $DB->get_records('local_formbuilder_forms', ['userid' => $userid]);
        if (!empty($forms)) {
            $data = [];
            foreach ($forms as $form) {
                $data[] = [
                    'name' => $form->name,
                    'description' => $form->description,
                    'timecreated' => transform::datetime($form->timecreated),
                    'timemodified' => transform::datetime($form->timemodified),
                ];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_formbuilder'), 'forms'], (object)$data);
        }

        // Export form submissions by user
        $submissions = $DB->get_records('local_formbuilder_submissions', ['userid' => $userid]);
        if (!empty($submissions)) {
            $data = [];
            foreach ($submissions as $submission) {
                $data[] = [
                    'formid' => $submission->formid,
                    'submissiondata' => $submission->submissiondata,
                    'timecreated' => transform::datetime($submission->timecreated),
                ];
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'local_formbuilder'), 'submissions'], (object)$data);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_formbuilder_forms');
            $DB->delete_records('local_formbuilder_submissions');
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_formbuilder_forms', ['userid' => $userid]);
        $DB->delete_records('local_formbuilder_submissions', ['userid' => $userid]);
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $userids = $userlist->get_userids();
        if (!empty($userids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_formbuilder_forms', "userid $insql", $inparams);
            $DB->delete_records_select('local_formbuilder_submissions', "userid $insql", $inparams);
        }
    }
}

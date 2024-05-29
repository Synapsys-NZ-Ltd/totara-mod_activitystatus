<?php

/**
 *
 * @package
 * @subpackage
 * @copyright   2019 Olumuyiwa Taiwo <muyi.taiwo@logicexpertise.com>
 * @author      Olumuyiwa Taiwo {@link https://moodle.org/user/view.php?id=416594}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_activitystatus_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019051600) {

        // Define field course to be added to activitystatus.
        $table = new xmldb_table('activitystatus');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'id');

        // Conditionally launch add field course.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Activitystatus savepoint reached.
        upgrade_mod_savepoint(true, 2019051600, 'activitystatus');
    }

    if ($oldversion < 2020010501) {

        // Define table activitystatus_displayorder to be created.
        $table = new xmldb_table('activitystatus_displayorder');

        // Adding fields to table activitystatus_displayorder.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('modid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseormodid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemtype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('displayorder', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table activitystatus_displayorder.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table activitystatus_displayorder.
        $table->add_index('activitystatus_displayorder_modid_idx', XMLDB_INDEX_NOTUNIQUE, array('modid'));

        // Conditionally launch create table for activitystatus_displayorder.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Activitystatus savepoint reached.
        upgrade_mod_savepoint(true, 2020010501, 'activitystatus');
    }

    if ($oldversion < 2021080400) {
        // Now using displayorder 0 to mean do not display.
        // Increment all existing displayorders
        $DB->execute('update {activitystatus_displayorder} set displayorder = displayorder + 1');

        upgrade_mod_savepoint(true, 2021080400, 'activitystatus');
    }

    if ($oldversion < 2021080500) {

        // Remove any orpahaned records
        $rs = $DB->get_recordset_sql("select o.id from {activitystatus_displayorder} o left join {course_modules} cm on cm.id = o.modid where o.itemtype = 'mod' and cm.id is null");
        foreach ($rs as $record) {
            $DB->delete_records('activitystatus_displayorder', ['id' => $record->id]);
        }

        // Properly set any modinstanceid where modinstanceid is 1
        $DB->execute("update {activitystatus_displayorder} set modinstanceid = (select instance from {course_modules} where id = modid) where modinstanceid = 0 and itemtype = 'mod'");

        upgrade_mod_savepoint(true, 2021080500, 'activitystatus');
    }

    if ($oldversion < 2021081300) {
        $rs = $DB->get_recordset('activitystatus_displayorder', ['modinstanceid' => '0', 'itemtype' => 'course']);
        foreach ($rs as $rec) {
            $instance = $DB->get_record('course_modules', ['id' => $rec->modid]);
            if ($instance) {
                $rec->modinstanceid = $instance->instance;
                $DB->update_record('activitystatus_displayorder', $rec);
            }
        }

        $concatsql = $DB->sql_concat('modid', 'itemtype', 'courseormodid');
        $duplicates = $DB->get_records_sql('select ' . $concatsql . ' "id", modid, modinstanceid, courseormodid, itemtype, count(id) "count" from {activitystatus_displayorder} group by modid, modinstanceid, courseormodid, itemtype');
        foreach ($duplicates as $item) {
            if ($item->count > 1) {
                $DB->delete_records('activitystatus_displayorder', ['modid' => $item->modid, 'modinstanceid' => $item->modinstanceid, 'itemtype' => $item->itemtype]);
            }
        }

        upgrade_mod_savepoint(true, 2021081300, 'activitystatus');
    }

    // Items per row setting
    if ($oldversion < 2024052900) {

        // Define field itemsperrow to be added to activitystatus.
        $table = new xmldb_table('activitystatus');
        $field = new xmldb_field('itemsperrow', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'timemodified');

        // Conditionally launch add field itemsperrow.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Activitystatus savepoint reached.
        upgrade_mod_savepoint(true, 2024052900, 'activitystatus');
    }

    return true;
}

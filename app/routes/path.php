<?php

define(
    "imgPath","http://localhost/finance_desktop_new_be/app/asset/"
);

// define("super_admin_url",'/finance_desktop_new_be/super_admin_api/');
// define("agent_url",'/finance_desktop_new_be/agent_api/');
define(
    "super_admin_url",
    '/finance_desktop_new_be-main/finance_desktop_new_be-main/super_admin_api/'
);

define(
    "agent_url",
    '/finance_desktop_new_be-main/finance_desktop_new_be-main/agent_api/'
);
// super_admin_url = '/finance_be/super_admin_api/';

$super_admin_class = 'Super_admin_controller';
$agent_class = 'Agent_controller';
$member_class = 'Member_controller';
$payment_class = 'Payment_controller';
$otp_class = 'Otp_controller';


define("routes", [
    'POST ' . super_admin_url . 'verify_otp_update_pass.php' => [$otp_class, 'verify_otp_update_pass'],
    'POST ' . super_admin_url . 'generate_otp.php' => [$otp_class, 'generate_otp'],


    'POST ' . super_admin_url . 'insert_agent.php' => [$agent_class, 'insert_agent'],
    'POST ' . super_admin_url . 'get_agents.php' => [$agent_class, 'get_agents'],
    'POST ' . super_admin_url . 'update_agent.php' => [$agent_class, 'update_agent'],
    'POST ' . super_admin_url . 'delete_agent.php' => [$agent_class, 'delete_agent'],
    'POST ' . super_admin_url . 'update_agent_password.php' => [$agent_class, 'update_agent_password'],

    'POST ' . super_admin_url . 'get_agent_names_and_areas.php' => [$agent_class, 'get_agent_names_and_areas'],
    'POST ' . super_admin_url . 'get_areas.php' => [$agent_class, 'get_areas'],
    'POST ' . super_admin_url . 'get_agent_names.php' => [$agent_class, 'get_agent_names'],
    'POST ' . super_admin_url . 'verify_agent.php' => [$agent_class, 'verify_agent'],
    'POST ' . super_admin_url . 'get_particular_agent.php' => [$agent_class, 'get_particular_agent'],

    'POST ' . super_admin_url . 'insert_member.php' => [$member_class, 'insert_member'],
    'POST ' . super_admin_url . 'insert_emi.php' => [$member_class, 'insert_emi'],
    'POST ' . super_admin_url . 'get_members.php' => [$member_class, 'get_members'],
    'POST ' . super_admin_url . 'get_emi_finished_members.php' => [$member_class, 'get_emi_finished_members'],
    'POST ' . super_admin_url . 'get_one_members.php' => [$member_class, 'get_one_members'],
    'POST ' . super_admin_url . 'get_members_pdf.php' => [$member_class, 'get_members_pdf'],
    'POST ' . super_admin_url . 'get_deleted_members.php' => [$member_class, 'get_deleted_members'],
    'POST ' . super_admin_url . 'get_after_members.php' => [$member_class, 'get_after_members'],
    'POST ' . super_admin_url . 'restore_member.php' => [$member_class, 'restore_member'],
    'POST ' . super_admin_url . 'delete_member.php' => [$member_class, 'delete_member'],
    'POST ' . super_admin_url . 'update_member.php' => [$member_class, 'update_member'],
    'POST ' . super_admin_url . 'insert_and_finish_emi.php' => [$member_class, 'insert_and_finish_emi'],
    'POST ' . super_admin_url . 'get_members_name_id.php' => [$member_class, 'get_members_name_id'],

    'POST ' . super_admin_url . 'get_payments.php' => [$payment_class, 'get_payments'],
    'POST ' . super_admin_url . 'get_new_entry_payments.php' => [$payment_class, 'get_new_entry_payments'],
    'POST ' . super_admin_url . 'insert_not_gived.php' => [$payment_class, 'insert_not_gived'],
    'POST ' . super_admin_url . 'get_payments_all.php' => [$payment_class, 'get_payments_all'],
    'POST ' . super_admin_url . 'update_payments.php' => [$payment_class, 'update_payments'],
    'POST ' . super_admin_url . 'insert_payments.php' => [$payment_class, 'insert_payments'],
    'POST ' . super_admin_url . 'insert_new_entry_payments.php' => [$payment_class, 'insert_new_entry_payments'],
    'POST ' . super_admin_url . 'insert_and_update_payments.php' => [$payment_class, 'insert_and_update_payments'],
    'POST ' . super_admin_url . 'get_collection_wise_reports.php' => [$payment_class, 'get_collection_wise_reports'],
    'POST ' . super_admin_url . 'get_customer_wise_reports.php' => [$payment_class, 'get_customer_wise_reports'],
    'POST ' . super_admin_url . 'get_new_loan_reports.php' => [$payment_class, 'get_new_loan_reports'],
]);
?>
<?php
/**
 * Language definitions for the PolarisMail module
 *
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2024, PolarisMail
 */

// Basics
$lang['Polarismail.name'] = 'PolarisMail';
$lang['Polarismail.description'] = 'Professional email hosting with advanced features';
$lang['Polarismail.module_row'] = 'Server';
$lang['Polarismail.module_row_plural'] = 'Servers';
$lang['Polarismail.module_group'] = 'Server Group';

// Module management
$lang['Polarismail.add_module_row'] = 'Add Server';
$lang['Polarismail.manage.module_rows_title'] = 'Servers';
$lang['Polarismail.manage.module_rows_heading.server_name'] = 'Server Label';
$lang['Polarismail.manage.module_rows_heading.username'] = 'Username';
$lang['Polarismail.manage.module_rows_heading.options'] = 'Options';
$lang['Polarismail.manage.module_rows.edit'] = 'Edit';
$lang['Polarismail.manage.module_rows.delete'] = 'Delete';
$lang['Polarismail.manage.module_rows.confirm_delete'] = 'Are you sure you want to delete this server?';
$lang['Polarismail.manage.module_rows_no_results'] = 'There are no servers.';

// Add row
$lang['Polarismail.row_meta.server_name'] = 'Server Label';
$lang['Polarismail.row_meta.username'] = 'API Username';
$lang['Polarismail.row_meta.password'] = 'API Password';
$lang['Polarismail.add_row.box_title'] = 'Add PolarisMail Server';
$lang['Polarismail.add_row.basic_title'] = 'Basic Settings';
$lang['Polarismail.add_row.add_btn'] = 'Add Server';

// Edit row
$lang['Polarismail.edit_row.box_title'] = 'Edit PolarisMail Server';
$lang['Polarismail.edit_row.basic_title'] = 'Basic Settings';
$lang['Polarismail.edit_row.add_btn'] = 'Update Server';

// Package fields
$lang['Polarismail.package_fields.account_type'] = 'Default Account Type';
$lang['Polarismail.package_fields.account_type_basic'] = 'Basic';
$lang['Polarismail.package_fields.account_type_enhanced'] = 'Enhanced';
$lang['Polarismail.package_fields.default_quota'] = 'Default Quota (GB)';
$lang['Polarismail.package_fields.default_quota_tooltip'] = 'The default mailbox quota in GB';
$lang['Polarismail.package_fields.twofa'] = 'Enable Two-Factor Authentication';
$lang['Polarismail.package_fields.accounts_access'] = 'Allow Accounts Management';
$lang['Polarismail.package_fields.aliases_access'] = 'Allow Aliases Management';
$lang['Polarismail.package_fields.forwards_access'] = 'Allow Forwards Management';
$lang['Polarismail.package_fields.lists_access'] = 'Allow Distribution Lists Management';
$lang['Polarismail.package_fields.extra_quota_increments'] = 'Extra Quota Increments (GB)';
$lang['Polarismail.package_fields.extra_quota_tooltip'] = 'Increments for extra quota configurable option, in GB';

// Configurable Options
$lang['Polarismail.options.extra_quota'] = 'Extra Quota (GB)';
$lang['Polarismail.options.extra_quota_description'] = 'Additional mailbox quota in gigabytes';

// Order Options
$lang['Polarismail.order_options.first'] = 'First Non-Full Server';

// Service fields
$lang['Polarismail.service_field.domain'] = 'Domain';
$lang['Polarismail.service_field.domain_tooltip'] = 'The domain name for the email service';
$lang['Polarismail.service_field.total_aliases'] = 'Total Aliases';
$lang['Polarismail.service_field.total_lists'] = 'Total Distribution Lists';

// Service info
$lang['Polarismail.service_info.domain'] = 'Domain';
$lang['Polarismail.service_info.username'] = 'Username';

// Tabs
$lang['Polarismail.tab_actions'] = 'Actions';
$lang['Polarismail.tab_client_accounts'] = 'Accounts';
$lang['Polarismail.tab_client_aliases'] = 'Aliases';
$lang['Polarismail.tab_client_forwards'] = 'Forwards';
$lang['Polarismail.tab_client_lists'] = 'Distribution Lists';
$lang['Polarismail.tab_client_dkim'] = 'DKIM Setup';
$lang['Polarismail.tab_client_branding'] = 'Branding';

// Client tab - Accounts
$lang['Polarismail.tab_client_accounts.heading'] = 'Email Accounts';
$lang['Polarismail.tab_client_accounts.heading_add'] = 'Add Email Account';
$lang['Polarismail.tab_client_accounts.heading_edit'] = 'Edit Email Account';
$lang['Polarismail.tab_client_accounts.field_username'] = 'Username';
$lang['Polarismail.tab_client_accounts.field_email'] = 'Email Address';
$lang['Polarismail.tab_client_accounts.field_password'] = 'Password';
$lang['Polarismail.tab_client_accounts.field_password_requirements'] = 'Minimum 8 characters, one uppercase, one number, one symbol. Example: 7uB8Hdp@';
$lang['Polarismail.tab_client_accounts.field_password_generate'] = 'Generate Secure Password';
$lang['Polarismail.tab_client_accounts.field_password_invalid'] = 'Password must be at least 8 characters and contain one uppercase letter, one number, and one symbol.';
$lang['Polarismail.tab_client_accounts.field_quota'] = 'Quota (GB)';
$lang['Polarismail.tab_client_accounts.field_account_type'] = 'Account Type';
$lang['Polarismail.tab_client_accounts.field_name'] = 'Display Name';
$lang['Polarismail.tab_client_accounts.field_status'] = 'Status';
$lang['Polarismail.tab_client_accounts.field_last_login'] = 'Last Login';
$lang['Polarismail.tab_client_accounts.field_usage'] = 'Usage';
$lang['Polarismail.tab_client_accounts.field_actions'] = 'Actions';
$lang['Polarismail.tab_client_accounts.option_add'] = 'Add Account';
$lang['Polarismail.tab_client_accounts.option_import'] = 'Import from CSV';
$lang['Polarismail.tab_client_accounts.option_edit'] = 'Edit';
$lang['Polarismail.tab_client_accounts.option_delete'] = 'Delete';
$lang['Polarismail.tab_client_accounts.option_enable'] = 'Enable';
$lang['Polarismail.tab_client_accounts.option_disable'] = 'Disable';
$lang['Polarismail.tab_client_accounts.option_webmail'] = 'Webmail';
$lang['Polarismail.tab_client_accounts.option_control_panel'] = 'Control Panel';
$lang['Polarismail.tab_client_accounts.text_no_accounts'] = 'No email accounts have been created yet.';
$lang['Polarismail.tab_client_accounts.confirm_delete'] = 'Are you sure you want to delete this email account?';
$lang['Polarismail.tab_client_accounts.account_limit'] = 'Account Limit Reached';
$lang['Polarismail.tab_client_accounts.quota_limit'] = 'Quota Limit Reached';
$lang['Polarismail.tab_client_accounts.status_active'] = 'Active';
$lang['Polarismail.tab_client_accounts.status_disabled'] = 'Disabled';
$lang['Polarismail.tab_client_accounts.card_manage_title'] = 'Email Accounts Management';
$lang['Polarismail.tab_client_accounts.card_overview_title'] = 'Account Overview';
$lang['Polarismail.tab_client_accounts.card_table_title'] = 'Email Accounts';
$lang['Polarismail.tab_client_accounts.card_table_subtitle'] = 'Manage your PolarisMail mailboxes in one place.';
$lang['Polarismail.tab_client_accounts.card_overview_description'] = 'Monitor mailbox totals, status, and quota allocation at a glance.';
$lang['Polarismail.tab_client_accounts.card_table_description'] = 'Review individual mailboxes, launch webmail, or adjust settings below.';
$lang['Polarismail.tab_client_accounts.description_manage'] = 'In this area you can manage email accounts associated with your domains.';
$lang['Polarismail.tab_client_accounts.description'] = 'Manage email accounts';
$lang['Polarismail.tab_client_accounts.metric_total'] = 'Total Accounts';
$lang['Polarismail.tab_client_accounts.metric_active'] = 'Active Accounts';
$lang['Polarismail.tab_client_accounts.metric_disabled'] = 'Disabled Accounts';
$lang['Polarismail.tab_client_accounts.metric_basic'] = 'Basic Accounts';
$lang['Polarismail.tab_client_accounts.metric_enhanced'] = 'Enhanced Accounts';
$lang['Polarismail.tab_client_accounts.metric_quota'] = 'Allocated Quota (GB)';
$lang['Polarismail.tab_client_accounts.csv_format'] = 'CSV columns: Account Type (1 = Basic, 2 = Enhanced), Username, Domain, Password, Quota (GB), Display Name.';
$lang['Polarismail.tab_client_accounts.csv_example'] = 'Example row: 1, sales, example.com, SecurePass!23, 5, Sales Team';
$lang['Polarismail.tab_client_accounts.modal_add_title'] = 'Add Email Account';
$lang['Polarismail.tab_client_accounts.modal_edit_title'] = 'Edit Email Account';
$lang['Polarismail.tab_client_accounts.modal_import_title'] = 'Import Email Accounts from CSV';
$lang['Polarismail.tab_client_accounts.modal_close'] = 'Cancel';
$lang['Polarismail.tab_client_accounts.modal_save'] = 'Save Changes';
$lang['Polarismail.tab_client_accounts.modal_import'] = 'Import';
$lang['Polarismail.tab_client_accounts.import_csv_instructions'] = 'CSV Instructions';
$lang['Polarismail.tab_client_accounts.import_csv_format'] = 'CSV Format:';
$lang['Polarismail.tab_client_accounts.import_csv_example'] = 'Example row:';
$lang['Polarismail.tab_client_accounts.import_csv_file'] = 'Select CSV File';
$lang['Polarismail.tab_client_accounts.import_csv_hint'] = 'Choose a CSV file containing email accounts to import';
$lang['Polarismail.tab_client_accounts.field_password_optional'] = 'Password (leave blank to keep current password)';
$lang['Polarismail.tab_client_accounts.success_added'] = 'Email account %1$s has been created.';
$lang['Polarismail.tab_client_accounts.success_updated'] = 'Email account %1$s has been updated.';
$lang['Polarismail.tab_client_accounts.success_delete'] = 'Email account %1$s has been deleted.';
$lang['Polarismail.tab_client_accounts.success_enable'] = 'Email account %1$s has been enabled.';
$lang['Polarismail.tab_client_accounts.success_disable'] = 'Email account %1$s has been disabled.';
$lang['Polarismail.tab_client_accounts.success_import'] = 'Email account %1$s was imported successfully.';
$lang['Polarismail.tab_client_accounts.error_domain_required'] = 'A domain is required to manage email accounts.';
$lang['Polarismail.tab_client_accounts.error_email_required'] = 'Please provide an email address for the mailbox.';
$lang['Polarismail.tab_client_accounts.error_password_required'] = 'Please provide a password for the mailbox.';
$lang['Polarismail.tab_client_accounts.error_quota_required'] = 'Please specify a quota greater than 0 GB.';
$lang['Polarismail.tab_client_accounts.error_username_required'] = 'A mailbox username is required.';
$lang['Polarismail.tab_client_accounts.error_domain_mismatch'] = 'The domain %1$s does not match %2$s.';
$lang['Polarismail.tab_client_accounts.error_basic_limit_reached'] = 'You have reached your limit of Basic Accounts: %1$s/%2$s';
$lang['Polarismail.tab_client_accounts.error_enhanced_limit_reached'] = 'You have reached your limit of Enhanced Accounts: %1$s/%2$s';
$lang['Polarismail.tab_client_accounts.error_basic_not_available'] = 'Basic accounts are not available for this service.';
$lang['Polarismail.tab_client_accounts.error_enhanced_not_available'] = 'Enhanced accounts are not available for this service.';
$lang['Polarismail.tab_client_accounts.error_total_quota_limit_reached'] = 'You have reached your total quota allocation: %1$s GB/%2$s GB.';
$lang['Polarismail.tab_client_accounts.error_action'] = 'Unable to process the request: %1$s';
$lang['Polarismail.tab_client_accounts.error_api'] = 'An unexpected API error occurred while processing the request.';
$lang['Polarismail.tab_client_accounts.error_unknown_action'] = 'The requested action is not supported.';
$lang['Polarismail.tab_client_accounts.error_import_upload'] = 'Unable to upload the CSV file. Please try again.';
$lang['Polarismail.tab_client_accounts.error_import_open'] = 'Could not open the uploaded CSV file.';
$lang['Polarismail.tab_client_accounts.error_import_row'] = 'Row %1$s: %2$s';

// Client tab - Aliases
$lang['Polarismail.tab_client_aliases.heading'] = 'Email Aliases';
$lang['Polarismail.tab_client_aliases.heading_add'] = 'Add Email Alias';
$lang['Polarismail.tab_client_aliases.field_alias'] = 'Alias';
$lang['Polarismail.tab_client_aliases.field_domain'] = 'Domain';
$lang['Polarismail.tab_client_aliases.field_forward'] = 'Forward To';
$lang['Polarismail.tab_client_aliases.field_actions'] = 'Actions';
$lang['Polarismail.tab_client_aliases.option_add'] = 'Add Alias';
$lang['Polarismail.tab_client_aliases.option_delete'] = 'Delete';
$lang['Polarismail.tab_client_aliases.text_no_aliases'] = 'No aliases have been created yet.';
$lang['Polarismail.tab_client_aliases.confirm_delete'] = 'Are you sure you want to delete this alias?';
$lang['Polarismail.tab_client_aliases.alias_limit'] = 'Alias Limit Reached';
$lang['Polarismail.tab_client_aliases.card_manage_title'] = 'Aliases Management';
$lang['Polarismail.tab_client_aliases.card_create_title'] = 'Create New Alias';
$lang['Polarismail.tab_client_aliases.card_table_title'] = 'Existing Aliases';
$lang['Polarismail.tab_client_aliases.description_manage'] = 'Email aliases allow you to receive mail at multiple addresses that forward to a single account.';
$lang['Polarismail.tab_client_aliases.description'] = 'Manage email aliases';
$lang['Polarismail.tab_client_aliases.description_create'] = 'Choose a mailbox and alias address to add another way for customers to reach you.';
$lang['Polarismail.tab_client_aliases.description_table'] = 'All aliases currently forwarding mail for this service are listed below.';
$lang['Polarismail.tab_client_aliases.success_added'] = 'Alias %1$s has been created.';
$lang['Polarismail.tab_client_aliases.success_deleted'] = 'Alias %1$s has been deleted.';
$lang['Polarismail.tab_client_aliases.error_domain_required'] = 'A domain is required to manage aliases.';
$lang['Polarismail.tab_client_aliases.error_alias_required'] = 'Please provide an alias.';
$lang['Polarismail.tab_client_aliases.error_forward_required'] = 'Please select a mailbox to forward to.';
$lang['Polarismail.tab_client_aliases.error_domain_mismatch'] = 'The domain %1$s does not match %2$s.';
$lang['Polarismail.tab_client_aliases.error_limit_reached'] = 'You have reached your alias limit: %1$s/%2$s.';
$lang['Polarismail.tab_client_aliases.error_action'] = 'Unable to process the request: %1$s';
$lang['Polarismail.tab_client_aliases.error_api'] = 'An unexpected API error occurred while processing the request.';
$lang['Polarismail.tab_client_aliases.error_unknown_action'] = 'The requested action is not supported.';
$lang['Polarismail.tab_client_aliases.error_no_accounts'] = 'Create a mailbox before adding aliases.';

// Client tab - Forwards
$lang['Polarismail.tab_client_forwards.heading'] = 'Email Forwarding';
$lang['Polarismail.tab_client_forwards.heading_add'] = 'Add Email Forward';
$lang['Polarismail.tab_client_forwards.field_account'] = 'Account';
$lang['Polarismail.tab_client_forwards.field_forward'] = 'Forward To';
$lang['Polarismail.tab_client_forwards.field_type'] = 'Forward Type';
$lang['Polarismail.tab_client_forwards.field_type_internal'] = 'Internal Account';
$lang['Polarismail.tab_client_forwards.field_type_external'] = 'External Email';
$lang['Polarismail.tab_client_forwards.field_actions'] = 'Actions';
$lang['Polarismail.tab_client_forwards.option_add'] = 'Add Forward';
$lang['Polarismail.tab_client_forwards.option_delete'] = 'Delete';
$lang['Polarismail.tab_client_forwards.text_no_forwards'] = 'No forwards have been created yet.';
$lang['Polarismail.tab_client_forwards.confirm_delete'] = 'Are you sure you want to delete this forward?';
$lang['Polarismail.tab_client_forwards.card_manage_title'] = 'Forwarding Management';
$lang['Polarismail.tab_client_forwards.card_create_title'] = 'Create New Forward';
$lang['Polarismail.tab_client_forwards.card_table_title'] = 'Existing Forwards';
$lang['Polarismail.tab_client_forwards.description_manage'] = 'Email forwards allow you to redirect messages from one address to another.';
$lang['Polarismail.tab_client_forwards.description'] = 'Manage email forwarding';
$lang['Polarismail.tab_client_forwards.description_create'] = 'Forward internally to another mailbox or to an external email address.';
$lang['Polarismail.tab_client_forwards.description_table'] = 'Existing forwarding rules for this service are listed below.';
$lang['Polarismail.tab_client_forwards.success_added'] = 'Forward created from %1$s to %2$s.';
$lang['Polarismail.tab_client_forwards.success_deleted'] = 'Forward removed from %1$s to %2$s.';
$lang['Polarismail.tab_client_forwards.error_domain_required'] = 'A domain is required to manage forwards.';
$lang['Polarismail.tab_client_forwards.error_account_required'] = 'Please select the mailbox to forward from.';
$lang['Polarismail.tab_client_forwards.error_forward_required'] = 'Please provide a forward destination.';
$lang['Polarismail.tab_client_forwards.error_internal_required'] = 'Please select the internal mailbox to forward to.';
$lang['Polarismail.tab_client_forwards.error_external_required'] = 'Please enter the external email address to forward to.';
$lang['Polarismail.tab_client_forwards.error_action'] = 'Unable to process the request: %1$s';
$lang['Polarismail.tab_client_forwards.error_api'] = 'An unexpected API error occurred while processing the request.';
$lang['Polarismail.tab_client_forwards.error_unknown_action'] = 'The requested action is not supported.';
$lang['Polarismail.tab_client_forwards.error_no_accounts'] = 'Create a mailbox before adding forwards.';

// Client tab - Distribution Lists
$lang['Polarismail.tab_client_lists.heading'] = 'Distribution Lists';
$lang['Polarismail.tab_client_lists.heading_add'] = 'Add Distribution List';
$lang['Polarismail.tab_client_lists.heading_edit'] = 'Edit Distribution List';
$lang['Polarismail.tab_client_lists.field_list_name'] = 'List Name';
$lang['Polarismail.tab_client_lists.field_list_type'] = 'List Type';
$lang['Polarismail.tab_client_lists.field_members'] = 'Members';
$lang['Polarismail.tab_client_lists.field_actions'] = 'Actions';
$lang['Polarismail.tab_client_lists.option_add'] = 'Add List';
$lang['Polarismail.tab_client_lists.option_edit'] = 'Edit';
$lang['Polarismail.tab_client_lists.option_delete'] = 'Delete';
$lang['Polarismail.tab_client_lists.text_no_lists'] = 'No distribution lists have been created yet.';
$lang['Polarismail.tab_client_lists.confirm_delete'] = 'Are you sure you want to delete this distribution list?';
$lang['Polarismail.tab_client_lists.list_limit'] = 'Distribution List Limit Reached';
$lang['Polarismail.tab_client_lists.card_manage_title'] = 'Distribution Lists Management';
$lang['Polarismail.tab_client_lists.card_table_title'] = 'Distribution Lists';
$lang['Polarismail.tab_client_lists.description_manage'] = 'A distribution list is a group of recipients reached from a single address.';
$lang['Polarismail.tab_client_lists.description'] = 'Manage distribution lists';
$lang['Polarismail.tab_client_lists.description_table'] = 'Distribution lists with their configured member counts are listed below.';
$lang['Polarismail.tab_client_lists.list_type_distribution'] = 'Distribution List';
$lang['Polarismail.tab_client_lists.members_hint'] = 'Hold Ctrl (Windows) or Command (Mac) to select multiple members.';
$lang['Polarismail.tab_client_lists.field_additional_members'] = 'Additional Members';
$lang['Polarismail.tab_client_lists.field_add_member'] = 'Add Member';
$lang['Polarismail.tab_client_lists.option_save'] = 'Save';
$lang['Polarismail.tab_client_lists.option_cancel'] = 'Cancel';
$lang['Polarismail.tab_client_lists.option_add_member'] = 'Add';
$lang['Polarismail.tab_client_lists.option_remove'] = 'Remove';
$lang['Polarismail.tab_client_lists.members_edit_hint'] = 'Remove members with the buttons, or add one email at a time.';
$lang['Polarismail.tab_client_lists.success_added'] = 'Distribution list %1$s has been created.';
$lang['Polarismail.tab_client_lists.success_updated'] = 'Distribution list %1$s has been updated.';
$lang['Polarismail.tab_client_lists.success_deleted'] = 'Distribution list %1$s has been deleted.';
$lang['Polarismail.tab_client_lists.error_domain_required'] = 'A domain is required to manage distribution lists.';
$lang['Polarismail.tab_client_lists.error_list_name_required'] = 'Please provide a list name.';
$lang['Polarismail.tab_client_lists.error_domain_mismatch'] = 'The domain %1$s does not match %2$s.';
$lang['Polarismail.tab_client_lists.error_limit_reached'] = 'You have reached your list limit: %1$s/%2$s.';
$lang['Polarismail.tab_client_lists.error_member_action'] = 'Unable to update member %1$s: %2$s';
$lang['Polarismail.tab_client_lists.error_action'] = 'Unable to process the request: %1$s';
$lang['Polarismail.tab_client_lists.error_api'] = 'An unexpected API error occurred while processing the request.';
$lang['Polarismail.tab_client_lists.error_unknown_action'] = 'The requested action is not supported.';
$lang['Polarismail.tab_client_lists.error_no_accounts'] = 'Create a mailbox before adding distribution lists.';

// Client tab - DKIM
$lang['Polarismail.tab_client_dkim.heading'] = 'DKIM Setup';
$lang['Polarismail.tab_client_dkim.field_status'] = 'DKIM Status';
$lang['Polarismail.tab_client_dkim.field_host'] = 'DKIM Host';
$lang['Polarismail.tab_client_dkim.field_key'] = 'DKIM Key';
$lang['Polarismail.tab_client_dkim.option_enable'] = 'Enable DKIM';
$lang['Polarismail.tab_client_dkim.option_disable'] = 'Disable DKIM';
$lang['Polarismail.tab_client_dkim.button_copy'] = 'Copy';
$lang['Polarismail.tab_client_dkim.description'] = 'Email authentication settings';
$lang['Polarismail.tab_client_dkim.description_long'] = 'DKIM is an email authentication method designed to detect forged sender addresses in emails, a technique often used in phishing and email spam (<a href="https://en.wikipedia.org/wiki/DomainKeys_Identified_Mail" target="_blank" rel="noopener noreferrer">Wikipedia</a>).';
$lang['Polarismail.tab_client_dkim.text_enabled'] = 'Enabled';
$lang['Polarismail.tab_client_dkim.text_disabled'] = 'Disabled';
$lang['Polarismail.tab_client_dkim.text_pending'] = 'Pending DNS Validation';
$lang['Polarismail.tab_client_dkim.text_instructions'] = 'Add the generated Host and domain Key from above to your DNS as a TXT record.';
$lang['Polarismail.tab_client_dkim.text_instructions_pending'] = 'Add the Host and Key values above to your DNS as a TXT record, then allow time for DNS propagation before checking the DKIM status again.';
$lang['Polarismail.tab_client_dkim.verification_heading'] = 'DNS Verification';
$lang['Polarismail.tab_client_dkim.verification_intro'] = 'Before you can use your service, you must verify the domain ownership!';
$lang['Polarismail.tab_client_dkim.verification_instruction'] = 'Please add the following record in DNS for %1$s';
$lang['Polarismail.tab_client_dkim.verification_host'] = 'Hostname';
$lang['Polarismail.tab_client_dkim.verification_type'] = 'Type';
$lang['Polarismail.tab_client_dkim.verification_value'] = 'Value';
$lang['Polarismail.tab_client_dkim.verification_host_value'] = 'mx-verification';
$lang['Polarismail.tab_client_dkim.verification_type_value'] = 'TXT';
$lang['Polarismail.tab_client_dkim.verification_recheck'] = 'Recheck Verification';
$lang['Polarismail.tab_client_dkim.verification_recheck_description'] = 'Already updated DNS? Recheck to see if verification has completed.';
$lang['Polarismail.verification_pending_action'] = 'Domain verification is still pending. Complete the DNS verification instructions to %1$s.';
$lang['Polarismail.verification_pending_action.accounts'] = 'View Accounts';
$lang['Polarismail.verification_pending_action.aliases'] = 'View Aliases';
$lang['Polarismail.verification_pending_action.forwards'] = 'View Forwards';
$lang['Polarismail.verification_pending_action.lists'] = 'View Distribution Lists';
$lang['Polarismail.verification_pending_action.dkim'] = 'View DKIM Setup';
$lang['Polarismail.verification_pending_action.branding'] = 'View Branding';
$lang['Polarismail.client_dashboard.heading'] = 'Manage Your Email Service';
$lang['Polarismail.client_dashboard.description'] = 'Choose a section below to manage your email service.';
$lang['Polarismail.client_dashboard.description_domain'] = 'Choose a section below to manage services for %1$s.';
$lang['Polarismail.client_dashboard.empty'] = 'No management sections are available for this service yet.';

// Client tab - Branding
$lang['Polarismail.tab_client_branding.heading'] = 'Email Branding';
$lang['Polarismail.tab_client_branding.field_brand_name'] = 'Brand Name';
$lang['Polarismail.tab_client_branding.field_support_email'] = 'Support Email';
$lang['Polarismail.tab_client_branding.field_brand_color'] = 'Brand Color';
$lang['Polarismail.tab_client_branding.field_logo'] = 'Logo';
$lang['Polarismail.tab_client_branding.option_update'] = 'Update Branding';
$lang['Polarismail.tab_client_branding.option_reset'] = 'Reset to Default';
$lang['Polarismail.tab_client_branding.text_current_logo'] = 'Current Logo';
$lang['Polarismail.tab_client_branding.confirm_reset'] = 'Are you sure you want to reset branding to default?';
$lang['Polarismail.tab_client_branding.card_manage_title'] = 'Domain Branding';
$lang['Polarismail.tab_client_branding.description_manage'] = 'Change the appearance of the webmail experience.';
$lang['Polarismail.tab_client_branding.description'] = 'Customize webmail appearance';
$lang['Polarismail.tab_client_branding.success_updated'] = 'Branding has been updated.';
$lang['Polarismail.tab_client_branding.success_reset'] = 'Branding has been reset to default.';
$lang['Polarismail.tab_client_branding.error_domain_required'] = 'A domain is required to manage branding.';
$lang['Polarismail.tab_client_branding.error_logo_size'] = 'Logo files must be smaller than 512 KB.';
$lang['Polarismail.tab_client_branding.error_action'] = 'Unable to process the request: %1$s';
$lang['Polarismail.tab_client_branding.error_api'] = 'An unexpected API error occurred while processing the request.';
$lang['Polarismail.tab_client_branding.error_unknown_action'] = 'The requested action is not supported.';

// Tooltips
$lang['Polarismail.service_field.tooltip.domain'] = 'Enter the domain name for this email service';
$lang['Polarismail.package_field.tooltip.account_type'] = 'Choose between Basic and Enhanced account types';

// Errors
$lang['Polarismail.!error.server_name.empty'] = 'Please enter a server label';
$lang['Polarismail.!error.username.empty'] = 'Please enter an API username';
$lang['Polarismail.!error.password.empty'] = 'Please enter an API password';
$lang['Polarismail.!error.password.invalid_credentials'] = 'Unable to connect to PolarisMail API. Please verify your username and password are correct.';
$lang['Polarismail.!error.meta[account_type].valid'] = 'Please select a valid account type';
$lang['Polarismail.!error.meta[default_quota].valid'] = 'Please enter a valid default quota';
$lang['Polarismail.!error.polarismail_domain.format'] = 'Please enter a valid domain name';
$lang['Polarismail.!error.api.internal'] = 'An internal error occurred, or the server did not respond to the request';
$lang['Polarismail.!error.module_row.missing'] = 'An internal error occurred. The module row is unavailable.';
$lang['Polarismail.!error.server_name_valid'] = 'You must enter a Server Label.';
$lang['Polarismail.!error.username_valid'] = 'You must enter a Username.';
$lang['Polarismail.!error.password_valid'] = 'You must enter a Password.';
$lang['Polarismail.!error.verification.still_pending'] = 'Domain verification is still pending. Please ensure you have added the DNS record and allow time for DNS propagation.';

// Success messages
$lang['Polarismail.!success.account_created'] = 'The email account has been created successfully.';
$lang['Polarismail.!success.account_updated'] = 'The email account has been updated successfully.';
$lang['Polarismail.!success.account_deleted'] = 'The email account has been deleted successfully.';
$lang['Polarismail.!success.account_enabled'] = 'The email account has been enabled successfully.';
$lang['Polarismail.!success.account_disabled'] = 'The email account has been disabled successfully.';
$lang['Polarismail.!success.alias_created'] = 'The alias has been created successfully.';
$lang['Polarismail.!success.alias_deleted'] = 'The alias has been deleted successfully.';
$lang['Polarismail.!success.forward_created'] = 'The forward has been created successfully.';
$lang['Polarismail.!success.forward_deleted'] = 'The forward has been deleted successfully.';
$lang['Polarismail.!success.list_created'] = 'The distribution list has been created successfully.';
$lang['Polarismail.!success.list_updated'] = 'The distribution list has been updated successfully.';
$lang['Polarismail.!success.list_deleted'] = 'The distribution list has been deleted successfully.';
$lang['Polarismail.!success.dkim_enabled'] = 'DKIM has been enabled successfully.';
$lang['Polarismail.!success.dkim_disabled'] = 'DKIM has been disabled successfully.';
$lang['Polarismail.!success.branding_updated'] = 'Branding has been updated successfully.';
$lang['Polarismail.!success.branding_reset'] = 'Branding has been reset to default successfully.';
$lang['Polarismail.!success.verification.complete'] = 'Domain verification is complete! You can now access all email management features.';

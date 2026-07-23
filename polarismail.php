<?php
/**
 * PolarisMail Module for Blesta
 *
 * This module provides integration with PolarisMail email hosting service.
 *
 * @package blesta
 * @subpackage blesta.components.modules.polarismail
 * @copyright Copyright (c) 2024, PolarisMail
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 */
class Polarismail extends Module
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('polarismail', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    /**
     * Returns an array of available service delegation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value pairs where the key
     *  is the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return ['first' => Language::_('Polarismail.order_options.first', true)];
    }

    /**
     * Returns the value used to identify a particular service
     *
     * @param stdClass $service A stdClass object representing the service
     * @return string A value used to identify this service
     */
    public function getServiceName($service)
    {
        foreach ($service->fields as $field) {
            if ($field->key == 'polarismail_domain') {
                return $field->value;
            }
        }
        return null;
    }

    /**
     * Returns a noun used to refer to a module row (e.g. "Server")
     *
     * @return string The noun used to refer to a module row
     */
    public function moduleRowName()
    {
        return Language::_('Polarismail.module_row', true);
    }

    /**
     * Returns a noun used to refer to a module row in plural form (e.g. "Servers")
     *
     * @return string The noun used to refer to a module row in plural form
     */
    public function moduleRowNamePlural()
    {
        return Language::_('Polarismail.module_row_plural', true);
    }

    /**
     * Returns a noun used to refer to a module group (e.g. "Server Group")
     *
     * @return string The noun used to refer to a module group
     */
    public function moduleGroupName()
    {
        return Language::_('Polarismail.module_group', true);
    }

    /**
     * Returns the key used to identify the primary field from the set of module row meta fields
     *
     * @return string The key used to identify the primary field from the set of module row meta fields
     */
    public function moduleRowMetaKey()
    {
        return 'server_name';
    }

    /**
     * Returns all tabs to display to an admin when managing a service
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title
     */
    public function getAdminTabs($package)
    {
        return [
            'tabActions' => Language::_('Polarismail.tab_actions', true)
        ];
    }

    /**
     * Returns all tabs to display to a client when managing a service
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title, or method => array where array contains:
     *  - name (required) The name of the link
     *  - icon (optional) use to display a custom icon
     *  - href (optional) use to link to a different URL
     */
    public function getClientTabs($package)
    {
        $tabs = [];

        if (!empty($package->meta->accounts_access)) {
            $tabs['tabClientAccounts'] = Language::_('Polarismail.tab_client_accounts', true);
        }

        if (!empty($package->meta->aliases_access)) {
            $tabs['tabClientAliases'] = Language::_('Polarismail.tab_client_aliases', true);
        }

        if (!empty($package->meta->forwards_access)) {
            $tabs['tabClientForwards'] = Language::_('Polarismail.tab_client_forwards', true);
        }

        if (!empty($package->meta->lists_access)) {
            $tabs['tabClientLists'] = Language::_('Polarismail.tab_client_lists', true);
        }

        $tabs['tabClientDkim'] = Language::_('Polarismail.tab_client_dkim', true);
        $tabs['tabClientBranding'] = Language::_('Polarismail.tab_client_branding', true);

        return $tabs;
    }

    /**
     * Returns HTML to insert into the service management page in the client interface.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param stdClass $service A stdClass object representing the selected service
     * @return string The rendered HTML content to display above the tabs
     */
    public function getClientManagementContent($package, $service)
    {
        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        // Check for POST action (e.g., verification recheck)
        $action = isset($_POST['action']) ? $_POST['action'] : null;

        $context = $this->buildDkimContext($module_row, $domain, [
            'include_dkim' => false,
            'action' => $action
        ]);
        $verification_required = !empty($context['verification_required']);

        // Set flash message if recheck was performed
        if (!empty($context['notice']) && !empty($context['notice']['message']) && $action === 'recheck') {
            // For warning messages (still pending), use 'error' type in Blesta's message system
            // For success messages (verification complete), use 'success' type
            $message_type = $context['notice']['type'] === 'success' ? 'success' : 'error';
            $this->setMessage($message_type, $context['notice']['message'], false, null, false);
        }

        $this->view = new View('client_management_verification', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        Loader::loadHelpers($this, ['Html', 'Form']);

        $client_tabs = $this->getClientTabs($package);
        $dashboard_tabs = [];
        $icon_map = [
            'tabClientAccounts' => 'bi bi-people',
            'tabClientAliases' => 'bi bi-at',
            'tabClientForwards' => 'bi bi-share',
            'tabClientLists' => 'bi bi-list-ul',
            'tabClientDkim' => 'bi bi-key',
            'tabClientBranding' => 'bi bi-brush'
        ];

        foreach ($client_tabs as $method => $tab) {
            $label = is_array($tab) ? ($tab['name'] ?? '') : $tab;
            if ($label === '') {
                continue;
            }

            $dashboard_tabs[] = [
                'method' => $method,
                'label' => $label,
                'href' => $this->getServiceManageUri($service, $method),
                'icon' => $icon_map[$method] ?? 'bi bi-gear'
            ];
        }

        $this->view->set('domain', $domain);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('manage_uri', $this->getServiceManageUri($service));
        $this->view->set('verification_required', $verification_required);
        $this->view->set('dashboard_tabs', $dashboard_tabs);
        // Don't pass notice to view - using setMessage() for flash messages instead
        $this->view->set('notice', null);

        return $this->view->fetch();
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Account Type
        $account_type = $fields->label(
            Language::_('Polarismail.package_fields.account_type', true),
            'polarismail_account_type'
        );
        $account_type->attach(
            $fields->fieldSelect(
                'meta[account_type]',
                [
                    'Basic' => Language::_('Polarismail.package_fields.account_type_basic', true),
                    'Enhanced' => Language::_('Polarismail.package_fields.account_type_enhanced', true)
                ],
                $this->Html->ifSet($vars->meta['account_type'], 'Basic'),
                ['id' => 'polarismail_account_type']
            )
        );
        $fields->setField($account_type);

        // Default Quota
        $default_quota = $fields->label(
            Language::_('Polarismail.package_fields.default_quota', true),
            'polarismail_default_quota'
        );
        $default_quota->attach(
            $fields->fieldText(
                'meta[default_quota]',
                $this->Html->ifSet($vars->meta['default_quota'], '10'),
                ['id' => 'polarismail_default_quota']
            )
        );
        $tooltip = $fields->tooltip(Language::_('Polarismail.package_fields.default_quota_tooltip', true));
        $default_quota->attach($tooltip);
        $fields->setField($default_quota);

        // Two-Factor Authentication
        $twofa = $fields->label(
            Language::_('Polarismail.package_fields.twofa', true),
            'polarismail_twofa'
        );
        $twofa->attach(
            $fields->fieldCheckbox(
                'meta[twofa]',
                '1',
                $this->Html->ifSet($vars->meta['twofa'], '1') == '1',
                ['id' => 'polarismail_twofa']
            )
        );
        $fields->setField($twofa);

        // Accounts Management Access
        $accounts_access = $fields->label(
            Language::_('Polarismail.package_fields.accounts_access', true),
            'polarismail_accounts_access'
        );
        $accounts_access->attach(
            $fields->fieldCheckbox(
                'meta[accounts_access]',
                '1',
                $this->Html->ifSet($vars->meta['accounts_access'], '1') == '1',
                ['id' => 'polarismail_accounts_access']
            )
        );
        $fields->setField($accounts_access);

        // Aliases Management Access
        $aliases_access = $fields->label(
            Language::_('Polarismail.package_fields.aliases_access', true),
            'polarismail_aliases_access'
        );
        $aliases_access->attach(
            $fields->fieldCheckbox(
                'meta[aliases_access]',
                '1',
                $this->Html->ifSet($vars->meta['aliases_access'], '1') == '1',
                ['id' => 'polarismail_aliases_access']
            )
        );
        $fields->setField($aliases_access);

        // Forwards Management Access
        $forwards_access = $fields->label(
            Language::_('Polarismail.package_fields.forwards_access', true),
            'polarismail_forwards_access'
        );
        $forwards_access->attach(
            $fields->fieldCheckbox(
                'meta[forwards_access]',
                '1',
                $this->Html->ifSet($vars->meta['forwards_access'], '1') == '1',
                ['id' => 'polarismail_forwards_access']
            )
        );
        $fields->setField($forwards_access);

        // Distribution Lists Management Access
        $lists_access = $fields->label(
            Language::_('Polarismail.package_fields.lists_access', true),
            'polarismail_lists_access'
        );
        $lists_access->attach(
            $fields->fieldCheckbox(
                'meta[lists_access]',
                '1',
                $this->Html->ifSet($vars->meta['lists_access'], '1') == '1',
                ['id' => 'polarismail_lists_access']
            )
        );
        $fields->setField($lists_access);

        // Extra Quota Increments
        $extra_quota = $fields->label(
            Language::_('Polarismail.package_fields.extra_quota_increments', true),
            'polarismail_extra_quota'
        );
        $extra_quota->attach(
            $fields->fieldText(
                'meta[extra_quota_increments]',
                $this->Html->ifSet($vars->meta['extra_quota_increments'], '1'),
                ['id' => 'polarismail_extra_quota']
            )
        );
        $tooltip = $fields->tooltip(Language::_('Polarismail.package_fields.extra_quota_tooltip', true));
        $extra_quota->attach($tooltip);
        $fields->setField($extra_quota);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Domain name
        $domain = $fields->label(Language::_('Polarismail.service_field.domain', true), 'polarismail_domain');
        $domain->attach(
            $fields->fieldText(
                'polarismail_domain',
                $this->Html->ifSet($vars->polarismail_domain),
                ['id' => 'polarismail_domain']
            )
        );
        $fields->setField($domain);

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Domain name
        $domain = $fields->label(Language::_('Polarismail.service_field.domain', true), 'polarismail_domain');
        $domain->attach(
            $fields->fieldText(
                'polarismail_domain',
                $this->Html->ifSet($vars->polarismail_domain, $this->Html->ifSet($vars->domain)),
                ['id' => 'polarismail_domain']
            )
        );
        $fields->setField($domain);

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     */
    public function getAdminEditFields($package, $vars = null)
    {
        return $this->getAdminAddFields($package, $vars);
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        return '';
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package)
    {
        return '';
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title
     */
    public function getAdminServiceTabs($service)
    {
        return [];
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package
     *
     * @param array An array of key/value pairs used to add a package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addPackage(array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Validates input data when attempting to edit a package, returns the meta
     * data to save when editing a package
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array An array of key/value pairs used to edit a package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editPackage($package, array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }

        return $meta;
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manager module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Fetch the module rows to ensure the manage view can list them
        Loader::loadModels($this, ['ModuleManager']);

        $module_rows = $module->rows ?? $this->ModuleManager->getRows($module->id);

        if ($module_rows instanceof stdClass) {
            $module_rows = [$module_rows];
        }

        if (!is_array($module_rows)) {
            $module_rows = [];
        }

        $this->view->set('module', $module);
        $this->view->set('module_rows', $module_rows);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit
     *  module row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        $this->view->set('vars', (object) $vars);

        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'username', 'password'];
        $encrypted_fields = ['password'];

        // Set unset fields
        $vars = array_merge(['server_name' => '', 'username' => '', 'password' => ''], $vars);

        // Auto-generate server_name from username if not provided
        if (empty($vars['server_name']) && !empty($vars['username'])) {
            $vars['server_name'] = 'PolarisMail - ' . $vars['username'];
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        $meta_fields = ['server_name', 'username', 'password'];
        $encrypted_fields = ['password'];

        // Set unset fields
        $vars = array_merge(['server_name' => '', 'username' => '', 'password' => ''], $vars);

        // Auto-generate server_name from username if not provided
        if (empty($vars['server_name']) && !empty($vars['username'])) {
            $vars['server_name'] = 'PolarisMail - ' . $vars['username'];
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Deletes the module row on the remote server
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row)
    {
        // Nothing to do
        return null;
    }

    /**
     * Returns all validation rules for adding/editing a module row
     *
     * @param array $vars An array of module row data
     * @return array An array of Input validation rules
     */
    private function getRowRules(&$vars)
    {
        $rules = [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Polarismail.!error.server_name.empty', true)
                ]
            ],
            'username' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Polarismail.!error.username.empty', true)
                ]
            ],
            'password' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Polarismail.!error.password.empty', true)
                ],
                'valid' => [
                    'rule' => [[$this, 'validateConnection'], $vars['username'] ?? ''],
                    'message' => Language::_('Polarismail.!error.password.invalid_credentials', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates that the API credentials are correct by attempting a login
     *
     * @param string $password The password to test
     * @param string $username The username to test
     * @return bool True if the credentials are valid, false otherwise
     */
    public function validateConnection($password, $username)
    {
        // Don't validate if either field is empty (let the empty rule handle that)
        if (empty($username) || empty($password)) {
            return true;
        }

        try {
            $api = $this->getApi($username, $password);
            $response = $this->apiRequest($api, 'login');

            return $this->isApiSuccess($response) && !empty($response->returndata);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns all validation rules for adding/editing a package
     *
     * @param array $vars An array of package data
     * @return array An array of Input validation rules
     */
    private function getPackageRules(array $vars = null)
    {
        $rules = [
            'meta[account_type]' => [
                'valid' => [
                    'rule' => ['in_array', ['Basic', 'Enhanced']],
                    'message' => Language::_('Polarismail.!error.meta[account_type].valid', true)
                ]
            ],
            'meta[default_quota]' => [
                'valid' => [
                    'rule' => 'is_numeric',
                    'message' => Language::_('Polarismail.!error.meta[default_quota].valid', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Returns all validation rules for adding/editing a service
     *
     * @param array $vars An array of service data
     * @return array An array of Input validation rules
     */
    private function getServiceRules(array $vars = null)
    {
        $rules = [
            'polarismail_domain' => [
                'format' => [
                    'rule' => [[$this, 'validateHostName']],
                    'message' => Language::_('Polarismail.!error.polarismail_domain.format', true)
                ]
            ]
        ];

        return $rules;
    }

    /**
     * Validates that the given hostname is valid
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        // Use Blesta's built-in validation helper
        Loader::loadHelpers($this, ['DataStructure']);

        // Basic domain/hostname validation using regex
        // Accept domains like example.com or mail.example.com
        if (preg_match('/^([a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $host_name)) {
            return true;
        }

        // Check if it's an IP address
        if (filter_var($host_name, FILTER_VALIDATE_IP)) {
            return true;
        }

        return false;
    }

    /**
     * Initializes the API and returns an instance of that object with the given $host, $user, and $pass set
     *
     * @param string $username The API username
     * @param string $password The API password
     * @return PolarisMail_Api The PolarisMail_Api instance
     */
    private function getApi($username, $password)
    {
        Loader::load(dirname(__FILE__) . DS . 'apis' . DS . 'polarismail_api.php');

        return new PolarisMail_Api($username, $password);
    }

    /**
     * Note: getModuleRow() is provided by the parent Module class and cannot be overridden
     * Use $this->getModuleRow($module_row_id) to fetch module rows
     */

    /**
     * Adds the service to the remote server
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon service
     *  and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        $row = $this->getModuleRow($package->module_row ?? null);

        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Polarismail.!error.module_row.missing', true)]]
            );
            return;
        }

        $api = $this->getApi($row->meta->username, $row->meta->password);

        // Set service fields
        $params = $this->getFieldsFromInput((array) $vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($status == 'active') {
            try {
                // Login to API
                $token = $api->login();
                if (!$token || !$token->returncode) {
                    $this->Input->setErrors(['api' => ['response' => Language::_('Polarismail.!error.api.internal', true)]]);
                    return;
                }

                // Check if domain is available
                $response = $api->isDomainAvailable($token->returndata, $params['domain']);
                if (!$response->returncode) {
                    $this->Input->setErrors(['api' => ['response' => $response->returndata]]);
                    return;
                }

                // Add domain
                $response = $api->addDomain($token->returndata, $params['domain']);
                if (!$response->returncode) {
                    $this->Input->setErrors(['api' => ['response' => $response->returndata]]);
                    return;
                }

                // Log the request
                $this->log($row->meta->username, serialize($params), 'input', true);
            } catch (Exception $e) {
                $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
                $this->log($row->meta->username, $e->getMessage(), 'output', false);
            }
        }

        // Return service fields
        return [
            [
                'key' => 'polarismail_domain',
                'value' => $params['domain'],
                'encrypted' => 0
            ]
        ];
    }

    /**
     * Edits the service on the remote server
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null)
    {
        return null; // All changes occur at the service level only
    }

    /**
     * Cancels the service on the remote server
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow($package->module_row ?? null))) {
            $api = $this->getApi($row->meta->username, $row->meta->password);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            try {
                // Login to API
                $token = $api->login();
                if (!$token || !$token->returncode) {
                    $this->Input->setErrors(['api' => ['response' => Language::_('Polarismail.!error.api.internal', true)]]);
                    return;
                }

                // Remove domain
                $response = $api->removeDomain($token->returndata, $service_fields->polarismail_domain);
                if (!$response->returncode) {
                    $this->Input->setErrors(['api' => ['response' => $response->returndata]]);
                    return;
                }

                $this->log($row->meta->username, serialize($service_fields), 'input', true);
            } catch (Exception $e) {
                $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
                $this->log($row->meta->username, $e->getMessage(), 'output', false);
            }
        }

        return null;
    }

    /**
     * Suspends the service on the remote server
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow($package->module_row ?? null))) {
            $api = $this->getApi($row->meta->username, $row->meta->password);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            try {
                // Login to API
                $token = $api->login();
                if (!$token || !$token->returncode) {
                    $this->Input->setErrors(['api' => ['response' => Language::_('Polarismail.!error.api.internal', true)]]);
                    return;
                }

                // Disable domain
                $response = $api->disableDomain($token->returndata, $service_fields->polarismail_domain);
                if (!$response->returncode) {
                    $this->Input->setErrors(['api' => ['response' => $response->returndata]]);
                    return;
                }

                $this->log($row->meta->username, serialize($service_fields), 'input', true);
            } catch (Exception $e) {
                $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
                $this->log($row->meta->username, $e->getMessage(), 'output', false);
            }
        }

        return null;
    }

    /**
     * Unsuspends the service on the remote server
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being unsuspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($row = $this->getModuleRow($package->module_row ?? null))) {
            $api = $this->getApi($row->meta->username, $row->meta->password);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            try {
                // Login to API
                $token = $api->login();
                if (!$token || !$token->returncode) {
                    $this->Input->setErrors(['api' => ['response' => Language::_('Polarismail.!error.api.internal', true)]]);
                    return;
                }

                // Enable domain
                $response = $api->enableDomain($token->returndata, $service_fields->polarismail_domain);
                if (!$response->returncode) {
                    $this->Input->setErrors(['api' => ['response' => $response->returndata]]);
                    return;
                }

                $this->log($row->meta->username, serialize($service_fields), 'input', true);
            } catch (Exception $e) {
                $this->Input->setErrors(['api' => ['internal' => $e->getMessage()]]);
                $this->log($row->meta->username, $e->getMessage(), 'output', false);
            }
        }

        return null;
    }

    /**
     * Allows the module to perform an action when the service is ready to renew
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being renewed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function renewService($package, $service, $parent_package = null, $parent_service = null)
    {
        return null; // Nothing to do
    }

    /**
     * Updates the package for the service on the remote server
     *
     * @param stdClass $package_from A stdClass object representing the current package
     * @param stdClass $package_to A stdClass object representing the new package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being changed (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function changeServicePackage(
        $package_from,
        $package_to,
        $service,
        $parent_package = null,
        $parent_service = null
    ) {
        return null; // Nothing to do (changes are applied at service level only)
    }

    /**
     * Validates input data when attempting to add a service
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise
     */
    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars));
        return $this->Input->validates($vars);
    }

    /**
     * Parses the response from the API into a stdClass object
     *
     * @param PolarisMail_Api_Response $response The response from the API
     * @return stdClass A stdClass object representing the response, null if the response was an error
     */
    private function parseResponse($response)
    {
        $row = $response->response();
        $success = $response->status() >= 200 && $response->status() < 300;

        // Log the response including the request method, when available
        $endpoint = '';
        $last_request = null;

        if (is_object($response) && method_exists($response, 'lastRequest')) {
            $last_request = $response->lastRequest();
        }

        if (is_array($last_request)) {
            $method = strtoupper($last_request['method'] ?? '');
            $url = $last_request['url'] ?? '';
            $endpoint = trim(($method ? $method . ' ' : '') . $url);
        } elseif (is_object($last_request)) {
            $method = strtoupper($last_request->method ?? '');
            $url = $last_request->url ?? '';
            $endpoint = trim(($method ? $method . ' ' : '') . $url);
        }

        if ($endpoint === '' && $last_request !== null) {
            if (is_array($last_request)) {
                $endpoint = $last_request['url'] ?? '';
            } elseif (is_object($last_request)) {
                $endpoint = $last_request->url ?? '';
            }
        }

        $raw_payload = $response;
        if (is_object($response) && method_exists($response, 'raw')) {
            $raw_payload = $response->raw();
        }

        $this->log(
            $endpoint !== '' ? $endpoint : 'API Response',
            $this->encodeLogOutput($raw_payload),
            'output',
            $success
        );

        // Return if any errors encountered
        if (!$success) {
            $this->Input->setErrors(['api' => [(isset($row->errors) ? $row->errors : Language::_('Polarismail.!error.api.internal', true))]]);
            return;
        }

        return $row;
    }

    /**
     * Invokes an API method and records the request/response pair in the module log
     *
     * @param PolarisMail_Api $api The API instance
     * @param string $method The API method to invoke
     * @param array $params Parameters to pass to the API method
     * @return mixed The API response
     * @throws Exception Thrown if the requested API method is unavailable
     */
    private function apiRequest(PolarisMail_Api $api, $method, array $params = [])
    {
        if (!is_callable([$api, $method])) {
            throw new Exception(sprintf('API method %s is not available.', $method));
        }

        $response = call_user_func_array([$api, $method], $params);

        if (method_exists($api, 'getLastRequest')) {
            $this->logApiTransaction($api, $response);
        }

        return $response;
    }

    /**
     * Logs the most recent API transaction
     *
     * @param PolarisMail_Api $api The API instance
     * @param mixed $response The decoded API response
     * @return void
     */
    private function logApiTransaction(PolarisMail_Api $api, $response)
    {
        $request = $api->getLastRequest();
        if (empty($request)) {
            return;
        }

        $method = strtoupper($request['method'] ?? 'POST');
        $url = $request['url'] ?? '';
        $endpoint = trim($method . ' ' . $url);

        $this->log(
            $endpoint,
            $this->encodeLogInput($request['data'] ?? []),
            'input',
            true
        );

        $raw_response = $api->getLastResponse();
        $this->log(
            $endpoint,
            $this->encodeLogOutput($raw_response !== null ? $raw_response : $response),
            'output',
            $this->isApiSuccess($response)
        );
    }

    /**
     * Encodes API request parameters for logging
     *
     * @param mixed $data The data to encode
     * @return string The encoded and sanitized data
     */
    private function encodeLogInput($data)
    {
        $normalized = $this->convertToArray($data);
        $sanitized = $this->maskSensitiveData($normalized);

        return serialize($sanitized);
    }

    /**
     * Encodes API responses for logging
     *
     * @param mixed $data The response data
     * @return string The encoded and sanitized data
     */
    private function encodeLogOutput($data)
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $sanitized = $this->maskSensitiveData($decoded, false);
                return serialize($sanitized);
            }

            return serialize($data);
        }

        $normalized = $this->convertToArray($data);
        $sanitized = $this->maskSensitiveData($normalized, false);

        return serialize($sanitized);
    }

    /**
     * Recursively converts data into an array representation
     *
     * @param mixed $data The data to convert
     * @return mixed The converted data
     */
    private function convertToArray($data)
    {
        if (is_array($data)) {
            $converted = [];
            foreach ($data as $key => $value) {
                $converted[$key] = $this->convertToArray($value);
            }

            return $converted;
        }

        if (is_object($data)) {
            return $this->convertToArray(get_object_vars($data));
        }

        return $data;
    }

    /**
     * Masks sensitive values in the supplied data structure
     *
     * @param mixed $data The data to sanitize
     * @return mixed The sanitized data
     */
    private function maskSensitiveData($data, $mask_return_data = true)
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $lower_key = strtolower($key);
                    if ($mask_return_data && $lower_key === 'returndata' && is_string($value)) {
                        $sanitized[$key] = $this->maskValue($value);
                        continue;
                    }

                    if ($this->isSensitiveKey($lower_key)) {
                        $sanitized[$key] = $this->maskValue($value);
                        continue;
                    }
                }

                $sanitized[$key] = $this->maskSensitiveData($value, $mask_return_data);
            }

            return $sanitized;
        }

        if (is_object($data)) {
            $sanitized = clone $data;
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $lower_key = strtolower($key);
                    if ($mask_return_data && $lower_key === 'returndata' && is_string($value)) {
                        $sanitized->{$key} = $this->maskValue($value);
                        continue;
                    }

                    if ($this->isSensitiveKey($lower_key)) {
                        $sanitized->{$key} = $this->maskValue($value);
                        continue;
                    }
                }

                $sanitized->{$key} = $this->maskSensitiveData($value, $mask_return_data);
            }

            return $sanitized;
        }

        return $data;
    }

    /**
     * Determines whether the given key represents sensitive data
     *
     * @param string $key The key name
     * @return bool True if the key should be masked, otherwise false
     */
    private function isSensitiveKey($key)
    {
        $sensitive = ['password', 'pass', 'token', 'apikey', 'api_key', 'auth', 'secret', 'opass', 'newlogo', 'dkim_key'];

        foreach ($sensitive as $needle) {
            if ($key === $needle || strpos($key, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Produces a masked value for logging
     *
     * @param mixed $value The value to mask
     * @return string The masked representation
     */
    private function maskValue($value)
    {
        return '***';
    }

    /**
     * Determines whether an API response indicates success
     *
     * @param mixed $response The API response
     * @return bool True if successful, otherwise false
     */
    private function isApiSuccess($response)
    {
        if (is_object($response) && isset($response->returncode)) {
            return (bool) $response->returncode;
        }

        if (is_array($response) && isset($response['returncode'])) {
            return (bool) $response['returncode'];
        }

        return $response !== false && $response !== null;
    }

    /**
     * Extracts a human readable error message from an API response
     *
     * @param mixed $response The API response
     * @return string The extracted message
     */
    private function getApiErrorMessage($response)
    {
        if (is_object($response)) {
            if (isset($response->returndata) && is_string($response->returndata)) {
                return $response->returndata;
            }

            if (isset($response->message) && is_string($response->message)) {
                return $response->message;
            }
        }

        if (is_array($response)) {
            if (isset($response['returndata']) && is_string($response['returndata'])) {
                return $response['returndata'];
            }

            if (isset($response['message']) && is_string($response['message'])) {
                return $response['message'];
            }
        }

        return Language::_('Polarismail.!error.api.internal', true);
    }

    /**
     * Extracts a human readable success message from an API response
     *
     * @param mixed $response The API response
     * @param string $default_language_key A fallback language key
     * @return string The extracted message
     */
    private function getApiResponseMessage($response, $default_language_key = null)
    {
        if (is_object($response) && isset($response->returndata)) {
            if (is_string($response->returndata)) {
                return $response->returndata;
            }

            if (is_scalar($response->returndata)) {
                return (string) $response->returndata;
            }

            return print_r($response->returndata, true);
        }

        if (is_array($response) && isset($response['returndata'])) {
            if (is_string($response['returndata'])) {
                return $response['returndata'];
            }

            if (is_scalar($response['returndata'])) {
                return (string) $response['returndata'];
            }

            return print_r($response['returndata'], true);
        }

        if ($default_language_key !== null) {
            return Language::_($default_language_key, true);
        }

        return '';
    }

    /**
     * Retrieves the accounts from the service
     *
     * @param stdClass $service An object representing the service
     * @param stdClass $package An object representing the package
     * @param int $offset The offset to start fetching results from
     * @param int $limit The number of results to fetch
     * @param string $search A search term to filter results
     * @return mixed An array of accounts or false on error
     */
    private function getAccounts($service, $package, $offset = 0, $limit = 25, $search = '')
    {
        $row = $this->getModuleRow($package->module_row ?? null);
        if (!$row) {
            return false;
        }

        $api = $this->getApi($row->meta->username, $row->meta->password);
        $service_fields = $this->serviceFieldsToObject($service->fields);

        try {
            // Login to API
            $token = $api->login();
            if (!$token || !$token->returncode) {
                return false;
            }

            // Get all users for the domain
            if (!empty($search)) {
                $response = $api->searchUsers($token->returndata, $service_fields->polarismail_domain, $search, true, $offset, $limit);
            } else {
                $response = $api->getAllUsersDomain($token->returndata, $service_fields->polarismail_domain, 0, true, $offset, $limit, 1);
            }

            if ($response->returncode) {
                return $response->returndata;
            }
        } catch (Exception $e) {
            // Log error
            $this->log($row->meta->username, $e->getMessage(), 'output', false);
        }

        return false;
    }

    /**
     * Retrieves all aliases for the service domain
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package object
     * @return mixed An array of aliases or false on error
     */
    private function getAliases($service, $package)
    {
        $row = $this->getModuleRow($package->module_row ?? null);
        if (!$row) {
            return false;
        }

        $api = $this->getApi($row->meta->username, $row->meta->password);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        try {
            $token = $api->login();
            if (!$token || !$token->returncode) {
                return false;
            }

            $response = $api->getAllAliasesDomain($token->returndata, $domain);

            if ($response->returncode) {
                return $response->returndata;
            }
        } catch (Exception $e) {
            $this->log($row->meta->username, $e->getMessage(), 'output', false);
        }

        return false;
    }

    /**
     * Retrieves all forwards for the service domain
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package object
     * @return mixed An array of forwards or false on error
     */
    private function getForwards($service, $package)
    {
        $row = $this->getModuleRow($package->module_row ?? null);
        if (!$row) {
            return false;
        }

        $api = $this->getApi($row->meta->username, $row->meta->password);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        try {
            $token = $api->login();
            if (!$token || !$token->returncode) {
                return false;
            }

            $response = $api->getAllForwardsDomain($token->returndata, $domain);

            if ($response->returncode) {
                return $response->returndata;
            }
        } catch (Exception $e) {
            $this->log($row->meta->username, $e->getMessage(), 'output', false);
        }

        return false;
    }

    /**
     * Retrieves all distribution lists for the service domain
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package object
     * @return mixed An array of lists or false on error
     */
    private function getLists($service, $package)
    {
        $row = $this->getModuleRow($package->module_row ?? null);
        if (!$row) {
            return false;
        }

        $api = $this->getApi($row->meta->username, $row->meta->password);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        try {
            $token = $api->login();
            if (!$token || !$token->returncode) {
                return false;
            }

            $response = $api->getAllListsDomain($token->returndata, $domain);

            if ($response->returncode) {
                return $response->returndata;
            }
        } catch (Exception $e) {
            $this->log($row->meta->username, $e->getMessage(), 'output', false);
        }

        return false;
    }

    /**
     * Retrieves members for a distribution list
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package object
     * @param string $list_name The list name
     * @return mixed An array of members or false on error
     */
    private function getListMembers($service, $package, $list_name)
    {
        $row = $this->getModuleRow($package->module_row ?? null);
        if (!$row) {
            return false;
        }

        $api = $this->getApi($row->meta->username, $row->meta->password);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        try {
            $token = $api->login();
            if (!$token || !$token->returncode) {
                return false;
            }

            $response = $api->getListMembers($token->returndata, $list_name, $domain);

            if ($response->returncode) {
                return $response->returndata;
            }
        } catch (Exception $e) {
            $this->log($row->meta->username, $e->getMessage(), 'output', false);
        }

        return false;
    }

    /**
     * Retrieves the account limits from config options, falling back to package defaults
     *
     * @param stdClass $service The service object
     * @param stdClass|null $package The package object
     * @return array An array with keys 'max_basic' and 'max_enhanced'
     */
    private function getAccountLimits($service, $package = null)
    {
        $limits = ['max_basic' => 0, 'max_enhanced' => 0];

        $service_options = $this->getServiceConfigOptions($service);
        $max_basic = $this->getOptionValueByNames(
            $service_options,
            ['basic mailboxes', 'basic_mailboxes', 'total mailboxes', 'total_mailboxes', 'total basic accounts', 'total_basic_accounts']
        );
        $max_enhanced = $this->getOptionValueByNames(
            $service_options,
            ['enhanced mailboxes', 'enhanced_mailboxes', 'total enhanced mailboxes', 'total_enhanced_mailboxes', 'total enhanced accounts', 'total_enhanced_accounts']
        );

        if ($max_basic <= 0) {
            $max_basic = $this->getOptionValueByNames(
                $this->getPackageConfigOptions($package),
                ['basic mailboxes', 'basic_mailboxes', 'total mailboxes', 'total_mailboxes', 'total basic accounts', 'total_basic_accounts']
            );
        }

        if ($max_enhanced <= 0) {
            $max_enhanced = $this->getOptionValueByNames(
                $this->getPackageConfigOptions($package),
                ['enhanced mailboxes', 'enhanced_mailboxes', 'total enhanced mailboxes', 'total_enhanced_mailboxes', 'total enhanced accounts', 'total_enhanced_accounts']
            );
        }

        // If no config option limits are configured, default to a single-account service
        // based on the package default account type.
        if ((int) $max_basic <= 0 && (int) $max_enhanced <= 0) {
            $default_account_type = $this->getDefaultAccountType($package);
            if ($default_account_type === 2) {
                $max_enhanced = 1;
                $max_basic = 0;
            } else {
                $max_basic = 1;
                $max_enhanced = 0;
            }
        }

        $limits['max_basic'] = (int) $max_basic;
        $limits['max_enhanced'] = (int) $max_enhanced;
        return $limits;
    }

    /**
     * Retrieves the alias limit from config options, falling back to package defaults
     *
     * @param stdClass $service The service object
     * @param stdClass|null $package The package object
     * @return int The maximum aliases allowed (0 means unlimited)
     */
    private function getAliasLimit($service, $package = null)
    {
        $max_aliases = $this->getOptionValueByNames(
            $this->getServiceConfigOptions($service),
            ['total aliases', 'total_aliases', 'aliases']
        );

        if ($max_aliases <= 0) {
            $max_aliases = $this->getOptionValueByNames(
                $this->getPackageConfigOptions($package),
                ['total aliases', 'total_aliases', 'aliases']
            );
        }

        return (int) $max_aliases;
    }

    /**
     * Retrieves the list limit from config options, falling back to package defaults
     *
     * @param stdClass $service The service object
     * @param stdClass|null $package The package object
     * @return int The maximum lists allowed (0 means unlimited)
     */
    private function getListLimit($service, $package = null)
    {
        $max_lists = $this->getOptionValueByNames(
            $this->getServiceConfigOptions($service),
            ['total distribution lists', 'total_distribution_lists', 'distribution lists', 'distribution_lists']
        );

        if ($max_lists <= 0) {
            $max_lists = $this->getOptionValueByNames(
                $this->getPackageConfigOptions($package),
                ['total distribution lists', 'total_distribution_lists', 'distribution lists', 'distribution_lists']
            );
        }

        return (int) $max_lists;
    }

    /**
     * Returns config options assigned to a service.
     *
     * @param stdClass $service The service object
     * @return array
     */
    private function getServiceConfigOptions($service)
    {
        if (!isset($service->id)) {
            return [];
        }

        if (!isset($this->Services)) {
            Loader::loadModels($this, ['Services']);
        }

        $options = $this->Services->getOptions($service->id);

        return is_array($options) ? $options : [];
    }

    /**
     * Returns package-level default config options from known package structures.
     *
     * @param stdClass|null $package The package object
     * @return array
     */
    private function getPackageConfigOptions($package = null)
    {
        if (!is_object($package)) {
            return [];
        }

        $candidates = [];

        foreach (['options', 'config_options', 'configoptions', 'package_options'] as $property) {
            if (isset($package->{$property})) {
                $value = $package->{$property};
                if (is_array($value)) {
                    $candidates = array_merge($candidates, $value);
                } elseif (is_object($value)) {
                    $candidates[] = $value;
                }
            }
        }

        if (isset($package->meta)) {
            if (is_array($package->meta)) {
                $candidates[] = $package->meta;
            } elseif (is_object($package->meta)) {
                $candidates[] = (array) $package->meta;
            }
        }

        return $candidates;
    }

    /**
     * Gets an integer option value by checking multiple possible option names.
     *
     * @param array $options A list of option objects/arrays
     * @param array $accepted_names Possible option names
     * @return int
     */
    private function getOptionValueByNames(array $options, array $accepted_names)
    {
        $accepted_lookup = [];
        foreach ($accepted_names as $name) {
            $accepted_lookup[strtolower(trim((string) $name))] = true;
        }

        foreach ($options as $option) {
            $normalized = is_object($option) ? (array) $option : (is_array($option) ? $option : []);
            if (empty($normalized)) {
                continue;
            }

            $name_candidates = [];
            foreach (['option_name', 'name', 'key', 'label'] as $name_key) {
                if (isset($normalized[$name_key])) {
                    $name_candidates[] = strtolower(trim((string) $normalized[$name_key]));
                }
            }

            // Handle package meta arrays where the key is the option name
            if (count($normalized) === 1) {
                $keys = array_keys($normalized);
                if (isset($keys[0])) {
                    $name_candidates[] = strtolower(trim((string) $keys[0]));
                }
            }

            $matched = false;
            foreach ($name_candidates as $candidate_name) {
                if ($candidate_name !== '' && isset($accepted_lookup[$candidate_name])) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                // Direct associative map fallback: ['basic_mailboxes' => 10]
                foreach ($accepted_lookup as $accepted_name => $unused) {
                    if (array_key_exists($accepted_name, $normalized)) {
                        return (int) $normalized[$accepted_name];
                    }
                }
                continue;
            }

            foreach (['value', 'selected_value', 'default', 'default_value', 'qty', 'quantity'] as $value_key) {
                if (isset($normalized[$value_key]) && is_numeric($normalized[$value_key])) {
                    return (int) $normalized[$value_key];
                }
            }
        }

        return 0;
    }

    /**
     * Returns the default account type (1=Basic, 2=Enhanced) from package settings.
     *
     * @param stdClass|null $package The package object
     * @return int
     */
    private function getDefaultAccountType($package = null)
    {
        $default_type = 1;

        if (is_object($package) && isset($package->meta->account_type)) {
            $account_type = strtolower(trim((string) $package->meta->account_type));
            if ($account_type === 'enhanced' || $account_type === '2') {
                $default_type = 2;
            }
        }

        $option_value = $this->getOptionValueByNames(
            $this->getPackageConfigOptions($package),
            ['account type', 'account_type', 'default account type', 'default_account_type']
        );

        if ($option_value === 2) {
            return 2;
        }

        return $default_type;
    }

    /**
     * Returns total mailbox quota limit (GB) from config options, with package fallback.
     *
     * @param stdClass $service The service object
     * @param stdClass|null $package The package object
     * @param array|null $limits Optional account limits array
     * @return float Total quota limit in GB (0 means unlimited)
     */
    private function getTotalQuotaLimitGb($service, $package = null, array $limits = null)
    {
        $option_names = [
            'total mailbox quota gb',
            'total_mailbox_quota_gb',
            'total quota gb',
            'total_quota_gb',
            'max quota gb',
            'max_quota_gb',
            'quota gb',
            'quota_gb',
            'mailbox quota gb',
            'mailbox_quota_gb'
        ];

        $limit = $this->getOptionValueByNames($this->getServiceConfigOptions($service), $option_names);
        if ($limit <= 0) {
            $limit = $this->getOptionValueByNames($this->getPackageConfigOptions($package), $option_names);
        }

        if ($limit > 0) {
            return (float) $limit;
        }

        if ($limits === null) {
            $limits = $this->getAccountLimits($service, $package);
        }

        $total_accounts = (int) ($limits['max_basic'] ?? 0) + (int) ($limits['max_enhanced'] ?? 0);
        if ($total_accounts <= 0) {
            return 0.0;
        }

        $default_quota = 10.0;
        if (is_object($package) && isset($package->meta->default_quota) && is_numeric($package->meta->default_quota)) {
            $default_quota = (float) $package->meta->default_quota;
        }
        if ($default_quota <= 0) {
            $default_quota = 10.0;
        }

        return (float) $total_accounts * $default_quota;
    }

    /**
     * Returns total currently allocated quota across accounts in GB.
     *
     * @param array $accounts
     * @return float
     */
    private function getTotalAllocatedQuotaGb(array $accounts)
    {
        $total = 0.0;

        foreach ($accounts as $account) {
            if (!isset($account->quota) || !is_numeric($account->quota)) {
                continue;
            }

            $raw_quota = (float) $account->quota;
            // PolarisMail API typically returns quota in MB, while inputs are GB.
            $quota_gb = $raw_quota > 1000 ? ($raw_quota / 1000) : $raw_quota;
            $total += $quota_gb;
        }

        return $total;
    }

    /**
     * Counts the number of accounts by type for a service
     *
     * @param stdClass $service The service object
     * @param stdClass $package The package object
     * @return array An array with keys 'basic' and 'enhanced' containing the counts
     */
    private function getAccountCountsByType($service, $package)
    {
        $counts = ['basic' => 0, 'enhanced' => 0];

        $accounts = $this->getAccounts($service, $package, 0, 9999);
        if (!is_array($accounts)) {
            return $counts;
        }

        foreach ($accounts as $account) {
            $account_type = isset($account->account_type) ? (int) $account->account_type : 1;
            if ($account_type === 2) {
                $counts['enhanced']++;
            } else {
                $counts['basic']++;
            }
        }

        return $counts;
    }

    /**
     * Handles client account actions for the Accounts tab.
     *
     * @param stdClass $module_row The module row used for API access
     * @param stdClass $package The package associated with the service
     * @param stdClass $service The service being managed
     * @param string $domain The domain tied to the service
     * @param array $post The POST payload
     * @param array $files The FILES payload
     * @param array $options Additional processing options
     * @return array A collection of status messages and form values
     */
    private function processClientAccountActions(
        $module_row,
        $package,
        $service,
        $domain,
        array $post = [],
        array $files = [],
        array $options = []
    ) {
        $results = [
            'success_messages' => [],
            'error_messages' => [],
            'import_success' => [],
            'import_errors' => [],
            'add_values' => [],
            'edit_values' => [],
            'show_add_modal' => false,
            'show_edit_modal' => false
        ];

        $has_post_action = !empty($post) && !empty($post['account_action']);
        $has_import = !empty($files['accounts_csv']['tmp_name'] ?? null);

        if (!$has_post_action && !$has_import) {
            return $results;
        }

        if (!$module_row || empty($module_row->meta->username) || empty($module_row->meta->password)) {
            $results['error_messages'][] = Language::_('Polarismail.!error.module_row.missing', true);
            return $results;
        }

        $domain = trim((string) $domain);
        if ($domain === '') {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_domain_required', true);
            return $results;
        }

        $twofa_allowed = isset($options['twofa_allowed']) ? (int) $options['twofa_allowed'] : 1;
        $default_account_type = isset($options['default_account_type']) ? (int) $options['default_account_type'] : 1;

        $api = $this->getApi($module_row->meta->username, $module_row->meta->password);

        try {
            $token_response = $this->apiRequest($api, 'login');
        } catch (Exception $e) {
            $token_response = null;
        }

        if (!$this->isApiSuccess($token_response) || empty($token_response->returndata)) {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_api', true);
            return $results;
        }

        $token = $token_response->returndata;
        $action = $has_post_action ? strtolower(trim($post['account_action'])) : null;
        $account_types = [1, 2];

        if ($action === 'add') {
            $values = [
                'account_email' => trim($post['account_email'] ?? ''),
                'password' => (string) ($post['password'] ?? ''),
                'quota' => trim($post['quota'] ?? ''),
                'account_type' => isset($post['account_type']) ? (int) $post['account_type'] : $default_account_type,
                'display_name' => trim($post['display_name'] ?? '')
            ];

            $results['add_values'] = $values;
            $results['show_add_modal'] = true;

            $email_input = $values['account_email'];
            if ($email_input === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_email_required', true);
                return $results;
            }

            $email_domain = $domain;
            $local_part = $email_input;
            if (strpos($email_input, '@') !== false) {
                $parts = explode('@', $email_input);
                $local_part = array_shift($parts);
                $email_domain = array_pop($parts);
                if ($email_domain === null || $email_domain === '') {
                    $email_domain = $domain;
                }
            }

            $local_part = trim((string) $local_part);
            $email_domain = strtolower(trim((string) $email_domain));

            if ($local_part === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_email_required', true);
                return $results;
            }

            if ($email_domain !== strtolower($domain)) {
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_accounts.error_domain_mismatch', true),
                    $email_domain,
                    $domain
                );
                return $results;
            }

            $password = trim($values['password']);
            if ($password === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_password_required', true);
                return $results;
            }

            $quota = (float) $values['quota'];
            if ($quota <= 0) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_quota_required', true);
                return $results;
            }

            $account_type = in_array($values['account_type'], $account_types, true)
                ? (int) $values['account_type']
                : $default_account_type;

            // Check account limits
            $limits = $this->getAccountLimits($service, $package);
            $max_basic = $limits['max_basic'];
            $max_enhanced = $limits['max_enhanced'];

            if (!$this->isAccountTypeAllowedByLimits($account_type, $max_basic, $max_enhanced)) {
                $results['error_messages'][] = $this->getAccountTypeUnavailableMessage($account_type);
                return $results;
            }

            $current_accounts = $this->getAccounts($service, $package, 0, 9999);
            $current_accounts = is_array($current_accounts) ? $current_accounts : [];

            $total_quota_limit_gb = $this->getTotalQuotaLimitGb($service, $package, $limits);
            if ($total_quota_limit_gb > 0) {
                $current_total_quota_gb = $this->getTotalAllocatedQuotaGb($current_accounts);
                if (($current_total_quota_gb + (float) $quota) > $total_quota_limit_gb) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_total_quota_limit_reached', true),
                        round($current_total_quota_gb, 2),
                        round($total_quota_limit_gb, 2)
                    );
                    return $results;
                }
            }

            // Only enforce limits if they are set (greater than 0)
            if ($max_basic > 0 || $max_enhanced > 0) {
                $current_counts = $this->getAccountCountsByType($service, $package);

                if ($account_type === 2) {
                    // Enhanced account
                    if ($max_enhanced > 0 && $current_counts['enhanced'] >= $max_enhanced) {
                        $results['error_messages'][] = sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_enhanced_limit_reached', true),
                            $current_counts['enhanced'],
                            $max_enhanced
                        );
                        return $results;
                    }
                } else {
                    // Basic account
                    if ($max_basic > 0 && $current_counts['basic'] >= $max_basic) {
                        $results['error_messages'][] = sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_basic_limit_reached', true),
                            $current_counts['basic'],
                            $max_basic
                        );
                        return $results;
                    }
                }
            }

            $params = [
                'username' => $local_part,
                'domain' => $domain,
                'account_type' => $account_type,
                'password' => $password,
                'quota' => $quota,
                'uname' => $values['display_name'],
                'twofa_allowed' => $twofa_allowed
            ];

            try {
                $response = $this->apiRequest($api, 'addUser', [$token, $params, 'en']);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_accounts.success_added', true),
                    $local_part . '@' . $domain
                );
                $results['add_values'] = [];
                $results['show_add_modal'] = false;
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_accounts.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_accounts.error_action', true),
                    $error_message
                );
                return $results;
            }
        } elseif ($action === 'edit') {
            $username = trim($post['account_username'] ?? '');
            $values = [
                'account_username' => $username,
                'account_email' => trim($post['account_email'] ?? ($username !== '' ? $username . '@' . $domain : '')),
                'quota' => trim($post['quota'] ?? ''),
                'account_type' => isset($post['account_type']) ? (int) $post['account_type'] : $default_account_type,
                'display_name' => trim($post['display_name'] ?? ''),
                'password' => (string) ($post['password'] ?? '')
            ];

            $results['edit_values'] = $values;
            $results['show_edit_modal'] = true;

            if ($username === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_username_required', true);
                return $results;
            }

            $quota = (float) $values['quota'];
            if ($quota <= 0) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_quota_required', true);
                return $results;
            }

            $account_type = in_array($values['account_type'], $account_types, true)
                ? (int) $values['account_type']
                : $default_account_type;

            // Check if account type is changing and validate limits
            $limits = $this->getAccountLimits($service, $package);
            $max_basic = $limits['max_basic'];
            $max_enhanced = $limits['max_enhanced'];

            if (!$this->isAccountTypeAllowedByLimits($account_type, $max_basic, $max_enhanced)) {
                $results['error_messages'][] = $this->getAccountTypeUnavailableMessage($account_type);
                return $results;
            }

            // Get current account info to check if type is changing
            $current_accounts = $this->getAccounts($service, $package, 0, 9999);
            $current_accounts = is_array($current_accounts) ? $current_accounts : [];
            $current_account_type = null;
            $current_account_quota_gb = 0.0;
            foreach ($current_accounts as $acc) {
                if (isset($acc->username) && $acc->username === $username) {
                    $current_account_type = isset($acc->account_type) ? (int) $acc->account_type : 1;
                    if (isset($acc->quota) && is_numeric($acc->quota)) {
                        $raw_current_quota = (float) $acc->quota;
                        $current_account_quota_gb = $raw_current_quota > 1000 ? ($raw_current_quota / 1000) : $raw_current_quota;
                    }
                    break;
                }
            }

            $total_quota_limit_gb = $this->getTotalQuotaLimitGb($service, $package, $limits);
            if ($total_quota_limit_gb > 0) {
                $current_total_quota_gb = $this->getTotalAllocatedQuotaGb($current_accounts);
                $proposed_total_quota_gb = $current_total_quota_gb - $current_account_quota_gb + (float) $quota;

                if ($proposed_total_quota_gb > $total_quota_limit_gb) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_total_quota_limit_reached', true),
                        round($current_total_quota_gb, 2),
                        round($total_quota_limit_gb, 2)
                    );
                    return $results;
                }
            }

            // Only validate if limits are set and account type is changing
            if (($max_basic > 0 || $max_enhanced > 0) && $current_account_type !== null && $current_account_type !== $account_type) {
                $current_counts = $this->getAccountCountsByType($service, $package);

                if ($account_type === 2) {
                    // Changing to Enhanced - check if enhanced limit would be exceeded
                    // Current enhanced count doesn't include this account yet (it's still basic)
                    if ($max_enhanced > 0 && $current_counts['enhanced'] >= $max_enhanced) {
                        $results['error_messages'][] = sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_enhanced_limit_reached', true),
                            $current_counts['enhanced'],
                            $max_enhanced
                        );
                        return $results;
                    }
                } else {
                    // Changing to Basic - check if basic limit would be exceeded
                    // Current basic count doesn't include this account yet (it's still enhanced)
                    if ($max_basic > 0 && $current_counts['basic'] >= $max_basic) {
                        $results['error_messages'][] = sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_basic_limit_reached', true),
                            $current_counts['basic'],
                            $max_basic
                        );
                        return $results;
                    }
                }
            }

            $user = [
                'editusername' => $username,
                'editdomain' => $domain,
                'edituname' => $values['display_name'],
                'editaccount_type' => $account_type,
                'editquota' => $quota
            ];

            if ($values['password'] !== '') {
                $user['editpassword'] = $values['password'];
            }

            try {
                $response = $this->apiRequest($api, 'updateUser', [$token, $user]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_accounts.success_updated', true),
                    $username . '@' . $domain
                );
                $results['edit_values'] = [];
                $results['show_edit_modal'] = false;
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_accounts.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_accounts.error_action', true),
                    $error_message
                );
                return $results;
            }
        } elseif (in_array($action, ['delete', 'enable', 'disable'], true)) {
            $username = trim($post['account_username'] ?? '');

            if ($username === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_username_required', true);
                return $results;
            }

            $method_map = [
                'delete' => 'removeUser',
                'enable' => 'enableUser',
                'disable' => 'disableUser'
            ];

            $method = $method_map[$action];

            try {
                $response = $this->apiRequest($api, $method, [$token, $username, $domain]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $message_key = 'Polarismail.tab_client_accounts.success_' . $action;
                $results['success_messages'][] = sprintf(
                    Language::_($message_key, true),
                    $username . '@' . $domain
                );
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_accounts.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_accounts.error_action', true),
                    $error_message
                );
                return $results;
            }
        } elseif ($action && $action !== 'import') {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_unknown_action', true);
            return $results;
        }

        if ($has_import) {
            $upload = $files['accounts_csv'];

            if (!is_array($upload)) {
                return $results;
            }

            if (!empty($upload['error']) && (int) $upload['error'] !== UPLOAD_ERR_OK) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_import_upload', true);
                return $results;
            }

            $path = $upload['tmp_name'];
            if (!is_uploaded_file($path) && !is_file($path)) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_import_upload', true);
                return $results;
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_accounts.error_import_open', true);
                return $results;
            }

            $import_limits = $this->getAccountLimits($service, $package);
            $import_max_basic = (int) ($import_limits['max_basic'] ?? 0);
            $import_max_enhanced = (int) ($import_limits['max_enhanced'] ?? 0);
            $import_counts = $this->getAccountCountsByType($service, $package);
            $import_current_accounts = $this->getAccounts($service, $package, 0, 9999);
            $import_current_accounts = is_array($import_current_accounts) ? $import_current_accounts : [];
            $import_total_quota_limit_gb = $this->getTotalQuotaLimitGb($service, $package, $import_limits);
            $import_current_total_quota_gb = $this->getTotalAllocatedQuotaGb($import_current_accounts);

            $row_index = 0;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $row_index++;

                $normalized = array_map(function ($value) {
                    return trim((string) $value);
                }, $row);

                if ($row_index === 1 && isset($normalized[0]) && !is_numeric($normalized[0]) && strtolower($normalized[0]) !== '1') {
                    // Skip header row
                    continue;
                }

                if (count(array_filter($normalized, function ($value) {
                    return $value !== '';
                })) === 0) {
                    continue;
                }

                $normalized = array_pad($normalized, 6, '');

                $import_type = (int) ($normalized[0] !== '' ? $normalized[0] : $default_account_type);
                $import_username = $normalized[1];
                $import_domain = $normalized[2] !== '' ? strtolower($normalized[2]) : strtolower($domain);
                $import_password = $normalized[3];
                $import_quota = (float) $normalized[4];
                $import_name = $normalized[5];

                if ($import_username === '') {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        Language::_('Polarismail.tab_client_accounts.error_username_required', true)
                    );
                    continue;
                }

                if ($import_domain !== strtolower($domain)) {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_domain_mismatch', true),
                            $import_domain,
                            $domain
                        )
                    );
                    continue;
                }

                if ($import_password === '') {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        Language::_('Polarismail.tab_client_accounts.error_password_required', true)
                    );
                    continue;
                }

                if ($import_quota <= 0) {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        Language::_('Polarismail.tab_client_accounts.error_quota_required', true)
                    );
                    continue;
                }

                $account_type = in_array($import_type, $account_types, true)
                    ? $import_type
                    : $default_account_type;

                if (!$this->isAccountTypeAllowedByLimits($account_type, $import_max_basic, $import_max_enhanced)) {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        $this->getAccountTypeUnavailableMessage($account_type)
                    );
                    continue;
                }

                if ($account_type === 2 && $import_max_enhanced > 0 && $import_counts['enhanced'] >= $import_max_enhanced) {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_enhanced_limit_reached', true),
                            $import_counts['enhanced'],
                            $import_max_enhanced
                        )
                    );
                    continue;
                }

                if ($account_type !== 2 && $import_max_basic > 0 && $import_counts['basic'] >= $import_max_basic) {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_basic_limit_reached', true),
                            $import_counts['basic'],
                            $import_max_basic
                        )
                    );
                    continue;
                }

                if ($import_total_quota_limit_gb > 0 && ($import_current_total_quota_gb + (float) $import_quota) > $import_total_quota_limit_gb) {
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        sprintf(
                            Language::_('Polarismail.tab_client_accounts.error_total_quota_limit_reached', true),
                            round($import_current_total_quota_gb, 2),
                            round($import_total_quota_limit_gb, 2)
                        )
                    );
                    continue;
                }

                $params = [
                    'username' => $import_username,
                    'domain' => $domain,
                    'account_type' => $account_type,
                    'password' => $import_password,
                    'quota' => $import_quota,
                    'uname' => $import_name,
                    'twofa_allowed' => $twofa_allowed
                ];

                try {
                    $response = $this->apiRequest($api, 'addUser', [$token, $params, 'en']);
                } catch (Exception $e) {
                    $response = null;
                }

                if ($this->isApiSuccess($response)) {
                    $results['import_success'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.success_import', true),
                        $import_username . '@' . $domain
                    );

                    if ($account_type === 2) {
                        $import_counts['enhanced'] = (int) $import_counts['enhanced'] + 1;
                    } else {
                        $import_counts['basic'] = (int) $import_counts['basic'] + 1;
                    }
                    $import_current_total_quota_gb += (float) $import_quota;
                } else {
                    $error_message = $this->getApiErrorMessage($response);
                    if ($error_message === '') {
                        $error_message = Language::_('Polarismail.tab_client_accounts.error_api', true);
                    }
                    $results['import_errors'][] = sprintf(
                        Language::_('Polarismail.tab_client_accounts.error_import_row', true),
                        $row_index,
                        $error_message
                    );
                }
            }

            fclose($handle);
        }

        return $results;
    }

    /**
     * Returns whether the requested account type is allowed by configured limits.
     *
     * If one account type has a positive limit and the other is zero, the zero-limit
     * type is treated as unavailable for this service.
     *
     * @param int $account_type Account type (1=Basic, 2=Enhanced)
     * @param int $max_basic Maximum basic accounts
     * @param int $max_enhanced Maximum enhanced accounts
     * @return bool
     */
    private function isAccountTypeAllowedByLimits($account_type, $max_basic, $max_enhanced)
    {
        $account_type = (int) $account_type;
        $max_basic = (int) $max_basic;
        $max_enhanced = (int) $max_enhanced;

        if ($account_type === 2 && $max_enhanced <= 0 && $max_basic > 0) {
            return false;
        }

        if ($account_type === 1 && $max_basic <= 0 && $max_enhanced > 0) {
            return false;
        }

        return true;
    }

    /**
     * Returns a localized account type unavailable error message.
     *
     * @param int $account_type Account type (1=Basic, 2=Enhanced)
     * @return string
     */
    private function getAccountTypeUnavailableMessage($account_type)
    {
        if ((int) $account_type === 2) {
            return Language::_('Polarismail.tab_client_accounts.error_enhanced_not_available', true);
        }

        return Language::_('Polarismail.tab_client_accounts.error_basic_not_available', true);
    }

    /**
     * Processes client alias actions (add/delete)
     *
     * @param stdClass $module_row The module row used for API access
     * @param stdClass $package The package associated with the service
     * @param stdClass $service The service being managed
     * @param string $domain The domain tied to the service
     * @param array $post The POST payload
     * @return array A collection of status messages and form values
     */
    private function processClientAliasActions(
        $module_row,
        $package,
        $service,
        $domain,
        array $post = []
    ) {
        $results = [
            'success_messages' => [],
            'error_messages' => [],
            'add_values' => []
        ];

        if (empty($post) || empty($post['alias_action'])) {
            return $results;
        }

        if (!$module_row || empty($module_row->meta->username) || empty($module_row->meta->password)) {
            $results['error_messages'][] = Language::_('Polarismail.!error.module_row.missing', true);
            return $results;
        }

        $domain = trim((string) $domain);
        if ($domain === '') {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_domain_required', true);
            return $results;
        }

        $api = $this->getApi($module_row->meta->username, $module_row->meta->password);

        try {
            $token_response = $this->apiRequest($api, 'login');
        } catch (Exception $e) {
            $token_response = null;
        }

        if (!$this->isApiSuccess($token_response) || empty($token_response->returndata)) {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_api', true);
            return $results;
        }

        $token = $token_response->returndata;
        $action = strtolower(trim((string) $post['alias_action']));

        if ($action === 'add') {
            $values = [
                'alias' => trim((string) ($post['alias'] ?? '')),
                'alias_domain' => trim((string) ($post['alias_domain'] ?? '')),
                'forward' => trim((string) ($post['forward'] ?? ''))
            ];

            $results['add_values'] = $values;

            if ($values['alias'] === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_alias_required', true);
                return $results;
            }

            if ($values['forward'] === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_forward_required', true);
                return $results;
            }

            $alias_local = $values['alias'];
            $alias_domain = $values['alias_domain'] !== '' ? $values['alias_domain'] : $domain;

            if (strpos($alias_local, '@') !== false) {
                $parts = explode('@', $alias_local);
                $alias_local = array_shift($parts);
                $alias_domain = $alias_domain !== '' ? $alias_domain : array_pop($parts);
            }

            $alias_local = trim((string) $alias_local);
            $alias_domain = strtolower(trim((string) $alias_domain));

            if ($alias_local === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_alias_required', true);
                return $results;
            }

            if ($alias_domain !== strtolower($domain)) {
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_aliases.error_domain_mismatch', true),
                    $alias_domain,
                    $domain
                );
                return $results;
            }

            $alias_limit = $this->getAliasLimit($service, $package);
            if ($alias_limit > 0) {
                try {
                    $aliases_response = $this->apiRequest($api, 'getAllAliasesDomain', [$token, $domain]);
                } catch (Exception $e) {
                    $aliases_response = null;
                }

                $aliases_list = [];
                if ($this->isApiSuccess($aliases_response) && isset($aliases_response->returndata) && is_array($aliases_response->returndata)) {
                    $aliases_list = $aliases_response->returndata;
                }

                if (count($aliases_list) >= $alias_limit) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_aliases.error_limit_reached', true),
                        count($aliases_list),
                        $alias_limit
                    );
                    return $results;
                }
            }

            try {
                $response = $this->apiRequest($api, 'addAlias', [$token, $alias_local, $domain, $values['forward']]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_aliases.success_added', true),
                    $alias_local . '@' . $domain
                );
                $results['add_values'] = [];
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_aliases.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_aliases.error_action', true),
                    $error_message
                );
                return $results;
            }
        } elseif ($action === 'delete') {
            $alias = trim((string) ($post['alias'] ?? ''));
            if ($alias === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_alias_required', true);
                return $results;
            }

            try {
                $response = $this->apiRequest($api, 'removeAlias', [$token, $alias, $domain]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_aliases.success_deleted', true),
                    $alias . '@' . $domain
                );
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_aliases.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_aliases.error_action', true),
                    $error_message
                );
                return $results;
            }
        } else {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_aliases.error_unknown_action', true);
        }

        return $results;
    }

    /**
     * Processes client forward actions (add/delete)
     *
     * @param stdClass $module_row The module row used for API access
     * @param stdClass $package The package associated with the service
     * @param stdClass $service The service being managed
     * @param string $domain The domain tied to the service
     * @param array $post The POST payload
     * @return array A collection of status messages and form values
     */
    private function processClientForwardActions(
        $module_row,
        $package,
        $service,
        $domain,
        array $post = []
    ) {
        $results = [
            'success_messages' => [],
            'error_messages' => [],
            'add_values' => []
        ];

        if (empty($post) || empty($post['forward_action'])) {
            return $results;
        }

        if (!$module_row || empty($module_row->meta->username) || empty($module_row->meta->password)) {
            $results['error_messages'][] = Language::_('Polarismail.!error.module_row.missing', true);
            return $results;
        }

        $domain = trim((string) $domain);
        if ($domain === '') {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_domain_required', true);
            return $results;
        }

        $api = $this->getApi($module_row->meta->username, $module_row->meta->password);

        try {
            $token_response = $this->apiRequest($api, 'login');
        } catch (Exception $e) {
            $token_response = null;
        }

        if (!$this->isApiSuccess($token_response) || empty($token_response->returndata)) {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_api', true);
            return $results;
        }

        $token = $token_response->returndata;
        $action = strtolower(trim((string) $post['forward_action']));

        if ($action === 'add') {
            $values = [
                'account' => trim((string) ($post['account'] ?? '')),
                'forward_type' => (int) ($post['forward_type'] ?? 1),
                'internal_account' => trim((string) ($post['internal_account'] ?? '')),
                'external_email' => trim((string) ($post['external_email'] ?? ''))
            ];

            $results['add_values'] = $values;

            if ($values['account'] === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_account_required', true);
                return $results;
            }

            $account_username = $values['account'];
            if (strpos($account_username, '@') !== false) {
                $parts = explode('@', $account_username);
                $account_username = array_shift($parts);
            }
            $account_username = trim((string) $account_username);
            if ($account_username === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_account_required', true);
                return $results;
            }

            $forward_email = '';
            if ($values['forward_type'] === 2) {
                if ($values['external_email'] === '') {
                    $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_external_required', true);
                    return $results;
                }
                $forward_email = $values['external_email'];
            } else {
                if ($values['internal_account'] === '') {
                    $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_internal_required', true);
                    return $results;
                }
                $internal_username = $values['internal_account'];
                if (strpos($internal_username, '@') !== false) {
                    $parts = explode('@', $internal_username);
                    $internal_username = array_shift($parts);
                }
                $internal_username = trim((string) $internal_username);
                if ($internal_username === '') {
                    $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_internal_required', true);
                    return $results;
                }
                $forward_email = $internal_username . '@' . $domain;
            }

            try {
                $otpass_response = $this->apiRequest($api, 'getOtPass', [$token, $account_username, $domain]);
            } catch (Exception $e) {
                $otpass_response = null;
            }

            if (!$this->isApiSuccess($otpass_response) || empty($otpass_response->returndata)) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_api', true);
                return $results;
            }

            try {
                $user_response = $this->apiRequest($api, 'userLogin', [$account_username, $otpass_response->returndata, $domain]);
            } catch (Exception $e) {
                $user_response = null;
            }

            if (!$this->isApiSuccess($user_response) || empty($user_response->returndata)) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_api', true);
                return $results;
            }

            try {
                $response = $this->apiRequest($api, 'addForward', [$user_response->returndata, $forward_email]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_forwards.success_added', true),
                    $account_username . '@' . $domain,
                    $forward_email
                );
                $results['add_values'] = [];
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_forwards.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_forwards.error_action', true),
                    $error_message
                );
                return $results;
            }
        } elseif ($action === 'delete') {
            $account = trim((string) ($post['account'] ?? ''));
            $forward = trim((string) ($post['forward'] ?? ''));

            if ($account === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_account_required', true);
                return $results;
            }

            if (strpos($account, '@') !== false) {
                $parts = explode('@', $account);
                $account = array_shift($parts);
            }
            $account = trim((string) $account);
            if ($account === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_account_required', true);
                return $results;
            }

            if ($forward === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_forward_required', true);
                return $results;
            }

            try {
                $otpass_response = $this->apiRequest($api, 'getOtPass', [$token, $account, $domain]);
            } catch (Exception $e) {
                $otpass_response = null;
            }

            if (!$this->isApiSuccess($otpass_response) || empty($otpass_response->returndata)) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_api', true);
                return $results;
            }

            try {
                $user_response = $this->apiRequest($api, 'userLogin', [$account, $otpass_response->returndata, $domain]);
            } catch (Exception $e) {
                $user_response = null;
            }

            if (!$this->isApiSuccess($user_response) || empty($user_response->returndata)) {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_api', true);
                return $results;
            }

            try {
                $response = $this->apiRequest($api, 'removeForward', [$user_response->returndata, $forward]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_forwards.success_deleted', true),
                    $account . '@' . $domain,
                    $forward
                );
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_forwards.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_forwards.error_action', true),
                    $error_message
                );
                return $results;
            }
        } else {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_forwards.error_unknown_action', true);
        }

        return $results;
    }

    /**
     * Processes client distribution list actions
     *
     * @param stdClass $module_row The module row used for API access
     * @param stdClass $package The package associated with the service
     * @param stdClass $service The service being managed
     * @param string $domain The domain tied to the service
     * @param array $post The POST payload
     * @return array A collection of status messages and form values
     */
    private function processClientListActions(
        $module_row,
        $package,
        $service,
        $domain,
        array $post = []
    ) {
        $results = [
            'success_messages' => [],
            'error_messages' => [],
            'add_values' => [],
            'edit_values' => [],
            'edit_list_name' => '',
            'edit_members' => []
        ];

        if (empty($post) || empty($post['list_action'])) {
            return $results;
        }

        if (!$module_row || empty($module_row->meta->username) || empty($module_row->meta->password)) {
            $results['error_messages'][] = Language::_('Polarismail.!error.module_row.missing', true);
            return $results;
        }

        $domain = trim((string) $domain);
        if ($domain === '') {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_domain_required', true);
            return $results;
        }

        $api = $this->getApi($module_row->meta->username, $module_row->meta->password);

        try {
            $token_response = $this->apiRequest($api, 'login');
        } catch (Exception $e) {
            $token_response = null;
        }

        if (!$this->isApiSuccess($token_response) || empty($token_response->returndata)) {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_api', true);
            return $results;
        }

        $token = $token_response->returndata;
        $action = strtolower(trim((string) $post['list_action']));

        if ($action === 'add') {
            $values = [
                'lname' => trim((string) ($post['lname'] ?? '')),
                'list_type' => (int) ($post['list_type'] ?? ($post['ltype'] ?? 1)),
                'members' => isset($post['members']) && is_array($post['members']) ? $post['members'] : [],
                'additional_members' => trim((string) ($post['additional_members'] ?? ''))
            ];

            $results['add_values'] = $values;

            if ($values['lname'] === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            $list_name = $values['lname'];
            $list_domain = $domain;
            if (strpos($list_name, '@') !== false) {
                $parts = explode('@', $list_name);
                $list_name = array_shift($parts);
                $list_domain = array_pop($parts);
                if ($list_domain === null || $list_domain === '') {
                    $list_domain = $domain;
                }
            }
            $list_name = trim((string) $list_name);
            $list_domain = strtolower(trim((string) $list_domain));

            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            if ($list_domain !== strtolower($domain)) {
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_lists.error_domain_mismatch', true),
                    $list_domain,
                    $domain
                );
                return $results;
            }

            $list_limit = $this->getListLimit($service, $package);
            if ($list_limit > 0) {
                try {
                    $lists_response = $this->apiRequest($api, 'getAllListsDomain', [$token, $domain]);
                } catch (Exception $e) {
                    $lists_response = null;
                }

                $lists_list = [];
                if ($this->isApiSuccess($lists_response) && isset($lists_response->returndata) && is_array($lists_response->returndata)) {
                    $lists_list = $lists_response->returndata;
                }

                if (count($lists_list) >= $list_limit) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_lists.error_limit_reached', true),
                        count($lists_list),
                        $list_limit
                    );
                    return $results;
                }
            }

            try {
                $response = $this->apiRequest($api, 'addList', [$token, $list_name, $domain, $values['list_type']]);
            } catch (Exception $e) {
                $response = null;
            }

            if (!$this->isApiSuccess($response)) {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_lists.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_lists.error_action', true),
                    $error_message
                );
                return $results;
            }

            $additional_members = [];
            if ($values['additional_members'] !== '') {
                $additional_members = array_filter(array_map('trim', explode(',', $values['additional_members'])));
            }
            $members = array_merge($values['members'], $additional_members);

            foreach ($members as $member) {
                $member = trim((string) $member);
                if ($member === '') {
                    continue;
                }

                try {
                    $member_response = $this->apiRequest($api, 'addListMember', [$token, $list_name, $domain, $member]);
                } catch (Exception $e) {
                    $member_response = null;
                }

                if (!$this->isApiSuccess($member_response)) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_lists.error_member_action', true),
                        $member,
                        $this->getApiErrorMessage($member_response)
                    );
                }
            }

            if (empty($results['error_messages'])) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_lists.success_added', true),
                    $list_name . '@' . $domain
                );
                $results['add_values'] = [];
            }
        } elseif ($action === 'delete') {
            $list_name = trim((string) ($post['lname'] ?? ''));
            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            if (strpos($list_name, '@') !== false) {
                $parts = explode('@', $list_name);
                $list_name = array_shift($parts);
            }
            $list_name = trim((string) $list_name);
            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            try {
                $response = $this->apiRequest($api, 'removeList', [$token, $list_name, $domain]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_lists.success_deleted', true),
                    $list_name . '@' . $domain
                );
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_lists.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_lists.error_action', true),
                    $error_message
                );
                return $results;
            }
        } elseif ($action === 'edit') {
            $list_name = trim((string) ($post['lname'] ?? ''));
            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            if (strpos($list_name, '@') !== false) {
                $parts = explode('@', $list_name);
                $list_name = array_shift($parts);
            }
            $list_name = trim((string) $list_name);
            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            try {
                $members_response = $this->apiRequest($api, 'getListMembers', [$token, $list_name, $domain]);
            } catch (Exception $e) {
                $members_response = null;
            }

            $members_list = [];
            if ($this->isApiSuccess($members_response) && isset($members_response->returndata) && is_array($members_response->returndata)) {
                foreach ($members_response->returndata as $member) {
                    if (isset($member->member)) {
                        $members_list[] = $member->member;
                    }
                }
            }

            $results['edit_list_name'] = $list_name;
            $results['edit_members'] = $members_list;
            $results['edit_values'] = [
                'lname' => $list_name,
                'members' => $members_list
            ];
        } elseif ($action === 'update') {
            $list_name = trim((string) ($post['lname'] ?? ''));
            $members = isset($post['members']) && is_array($post['members']) ? $post['members'] : [];
            $additional_members = trim((string) ($post['additional_members'] ?? ''));

            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            if (strpos($list_name, '@') !== false) {
                $parts = explode('@', $list_name);
                $list_name = array_shift($parts);
            }
            $list_name = trim((string) $list_name);
            if ($list_name === '') {
                $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_list_name_required', true);
                return $results;
            }

            try {
                $members_response = $this->apiRequest($api, 'getListMembers', [$token, $list_name, $domain]);
            } catch (Exception $e) {
                $members_response = null;
            }

            $existing_members = [];
            if ($this->isApiSuccess($members_response) && isset($members_response->returndata) && is_array($members_response->returndata)) {
                foreach ($members_response->returndata as $member) {
                    if (isset($member->member)) {
                        $existing_members[] = $member->member;
                    }
                }
            }

            $members = array_filter(array_map('trim', $members));
            if ($additional_members !== '') {
                $members = array_merge($members, array_filter(array_map('trim', explode(',', $additional_members))));
            }
            $to_add = array_diff($members, $existing_members);
            $to_remove = array_diff($existing_members, $members);

            foreach ($to_add as $member) {
                try {
                    $member_response = $this->apiRequest($api, 'addListMember', [$token, $list_name, $domain, $member]);
                } catch (Exception $e) {
                    $member_response = null;
                }

                if (!$this->isApiSuccess($member_response)) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_lists.error_member_action', true),
                        $member,
                        $this->getApiErrorMessage($member_response)
                    );
                }
            }

            foreach ($to_remove as $member) {
                try {
                    $member_response = $this->apiRequest($api, 'removeListMember', [$token, $list_name, $domain, $member]);
                } catch (Exception $e) {
                    $member_response = null;
                }

                if (!$this->isApiSuccess($member_response)) {
                    $results['error_messages'][] = sprintf(
                        Language::_('Polarismail.tab_client_lists.error_member_action', true),
                        $member,
                        $this->getApiErrorMessage($member_response)
                    );
                }
            }

            if (empty($results['error_messages'])) {
                $results['success_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_lists.success_updated', true),
                    $list_name . '@' . $domain
                );
            } else {
                $results['edit_list_name'] = $list_name;
                $results['edit_members'] = $members;
                $results['edit_values'] = [
                    'lname' => $list_name,
                    'members' => $members
                ];
            }
        } else {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_lists.error_unknown_action', true);
        }

        return $results;
    }

    /**
     * Processes client branding actions
     *
     * @param stdClass $module_row The module row used for API access
     * @param string $domain The domain tied to the service
     * @param array $post The POST payload
     * @param array $files The FILES payload
     * @return array A collection of status messages and form values
     */
    private function processClientBrandingActions(
        $module_row,
        $domain,
        array $post = [],
        array $files = []
    ) {
        $results = [
            'success_messages' => [],
            'error_messages' => [],
            'brand_values' => []
        ];

        if (empty($post) || empty($post['branding_action'])) {
            return $results;
        }

        if (!$module_row || empty($module_row->meta->username) || empty($module_row->meta->password)) {
            $results['error_messages'][] = Language::_('Polarismail.!error.module_row.missing', true);
            return $results;
        }

        $domain = trim((string) $domain);
        if ($domain === '') {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_branding.error_domain_required', true);
            return $results;
        }

        $api = $this->getApi($module_row->meta->username, $module_row->meta->password);

        try {
            $token_response = $this->apiRequest($api, 'login');
        } catch (Exception $e) {
            $token_response = null;
        }

        if (!$this->isApiSuccess($token_response) || empty($token_response->returndata)) {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_branding.error_api', true);
            return $results;
        }

        $token = $token_response->returndata;
        $action = strtolower(trim((string) $post['branding_action']));

        if ($action === 'update') {
            $values = [
                'brand_name' => trim((string) ($post['brand_name'] ?? '')),
                'support_email' => trim((string) ($post['support_email'] ?? '')),
                'brand_color' => trim((string) ($post['brand_color'] ?? ''))
            ];

            $results['brand_values'] = $values;

            if ($values['brand_color'] !== '' && strpos($values['brand_color'], '#') === 0) {
                $values['brand_color'] = ltrim($values['brand_color'], '#');
            }

            $new_logo = null;
            if (!empty($files['logo']['tmp_name'])) {
                if ($files['logo']['size'] > 512000) {
                    $results['error_messages'][] = Language::_('Polarismail.tab_client_branding.error_logo_size', true);
                    return $results;
                }
                $new_logo = file_get_contents($files['logo']['tmp_name']);
            }

            try {
                $response = $this->apiRequest(
                    $api,
                    'setDomainBranding',
                    [$token, $domain, $values['brand_name'], $values['support_email'], $values['brand_color'], $new_logo]
                );
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = Language::_('Polarismail.tab_client_branding.success_updated', true);
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_branding.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_branding.error_action', true),
                    $error_message
                );
            }
        } elseif ($action === 'reset') {
            try {
                $response = $this->apiRequest($api, 'resetDomainBranding', [$token, $domain]);
            } catch (Exception $e) {
                $response = null;
            }

            if ($this->isApiSuccess($response)) {
                $results['success_messages'][] = Language::_('Polarismail.tab_client_branding.success_reset', true);
            } else {
                $error_message = $this->getApiErrorMessage($response);
                if ($error_message === '') {
                    $error_message = Language::_('Polarismail.tab_client_branding.error_api', true);
                }
                $results['error_messages'][] = sprintf(
                    Language::_('Polarismail.tab_client_branding.error_action', true),
                    $error_message
                );
            }
        } else {
            $results['error_messages'][] = Language::_('Polarismail.tab_client_branding.error_unknown_action', true);
        }

        return $results;
    }

    /**
     * Returns an array of service field to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        $fields = [
            'domain' => isset($vars['polarismail_domain']) ? strtolower($vars['polarismail_domain']) : null
        ];

        return $fields;
    }

    /**
     * Converts a list of service fields into a stdClass object
     *
     * @param array $fields An array of service fields
     * @return stdClass A stdClass object representing the service fields
     */
    protected function serviceFieldsToObject(array $fields)
    {
        $object = new stdClass();

        foreach ($fields as $field) {
            $object->{$field->key} = $field->value;
        }

        return $object;
    }

    /**
     * Builds the service management URI for the client area, optionally targeting a tab.
     *
     * @param stdClass $service The service being managed
     * @param string $tab Optional tab identifier to append as a query parameter
     * @return string The generated URI
     */
    private function getServiceManageUri($service, $tab = null)
    {
        $base_uri = isset($this->base_uri) ? trim($this->base_uri) : '';
        if ($base_uri === '/' || $base_uri === '') {
            $base_uri = '';
        } else {
            $base_uri = '/' . trim($base_uri, '/');
        }
        $service_id = isset($service->id) ? $service->id : null;

        if (!$service_id) {
            return ($base_uri !== '' ? $base_uri : '') . '/services/';
        }

        $uri = $base_uri . '/services/manage/' . $service_id . '/';

        if (!empty($tab)) {
            $uri .= rawurlencode(ltrim($tab, '/')) . '/';
        }

        return $uri;
    }

    /**
     * Client Accounts tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientAccounts($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_accounts', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        $context = $this->buildDkimContext($module_row, $domain, ['include_dkim' => false]);
        $verification_required = !empty($context['verification_required']);

        $account_types = [
            1 => Language::_('Polarismail.package_fields.account_type_basic', true),
            2 => Language::_('Polarismail.package_fields.account_type_enhanced', true)
        ];
        $default_quota = isset($package->meta->default_quota) ? $package->meta->default_quota : '10';
        $default_account_type = $this->getDefaultAccountType($package);
        $twofa_allowed = !empty($package->meta->twofa) ? 1 : 0;

        $action_results = [
            'success_messages' => [],
            'error_messages' => [],
            'import_success' => [],
            'import_errors' => [],
            'add_values' => [],
            'edit_values' => [],
            'show_add_modal' => false,
            'show_edit_modal' => false
        ];

        if (!$verification_required) {
            $action_results = $this->processClientAccountActions(
                $module_row,
                $package,
                $service,
                $domain,
                $post ?? [],
                $files ?? [],
                [
                    'twofa_allowed' => $twofa_allowed,
                    'default_account_type' => $default_account_type
                ]
            );

            $success_messages = array_merge(
                is_array($action_results['success_messages'] ?? null) ? $action_results['success_messages'] : [],
                is_array($action_results['import_success'] ?? null) ? $action_results['import_success'] : []
            );
            if (!empty($success_messages)) {
                $this->setMessage('success', implode('<br />', $success_messages), false, null, false);
            }

            $error_messages = array_merge(
                is_array($action_results['error_messages'] ?? null) ? $action_results['error_messages'] : [],
                is_array($action_results['import_errors'] ?? null) ? $action_results['import_errors'] : []
            );
            if (!empty($error_messages)) {
                $this->setMessage('error', implode('<br />', $error_messages), false, null, false);
            }
        }

        $accounts = [];
        if (!$verification_required) {
            $accounts = $this->getAccounts($service, $package);
        }

        // Get account limits and current counts
        $limits = $this->getAccountLimits($service, $package);
        $max_basic_accounts = $limits['max_basic'];
        $max_enhanced_accounts = $limits['max_enhanced'];
        $account_counts = ['basic' => 0, 'enhanced' => 0];
        if (!$verification_required) {
            $account_counts = $this->getAccountCountsByType($service, $package);
        }

        $this->view->set('module_row', $module_row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('accounts', $accounts);
        $this->view->set('verification_required', $verification_required);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('pending_manage_uri', $this->getServiceManageUri($service, 'tabClientAccounts'));
        $this->view->set('account_types', $account_types);
        $this->view->set('default_quota', $default_quota);
        $this->view->set('default_account_type', $default_account_type);
        $this->view->set('twofa_allowed', $twofa_allowed);
        $this->view->set('service_domain', $domain);
        $this->view->set('csv_format', Language::_('Polarismail.tab_client_accounts.csv_format', true));
        $this->view->set('csv_example', Language::_('Polarismail.tab_client_accounts.csv_example', true));
        $this->view->set('max_basic_accounts', $max_basic_accounts);
        $this->view->set('max_enhanced_accounts', $max_enhanced_accounts);
        $this->view->set('current_basic_accounts', $account_counts['basic']);
        $this->view->set('current_enhanced_accounts', $account_counts['enhanced']);
        $this->view->set('success_messages', []);
        $this->view->set('error_messages', []);
        $this->view->set('import_success', []);
        $this->view->set('import_errors', []);
        $this->view->set('add_values', $action_results['add_values']);
        $this->view->set('edit_values', $action_results['edit_values']);
        $this->view->set('show_add_modal', $action_results['show_add_modal']);
        $this->view->set('show_edit_modal', $action_results['show_edit_modal']);

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Aliases tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientAliases($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_aliases', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        $context = $this->buildDkimContext($module_row, $domain, ['include_dkim' => false]);
        $verification_required = !empty($context['verification_required']);

        $action_results = [
            'success_messages' => [],
            'error_messages' => [],
            'add_values' => []
        ];

        if (!$verification_required) {
            $action_results = $this->processClientAliasActions(
                $module_row,
                $package,
                $service,
                $domain,
                $post ?? []
            );

            if (!empty($action_results['success_messages'])) {
                $this->setMessage('success', implode('<br />', $action_results['success_messages']), false, null, false);
            }
            if (!empty($action_results['error_messages'])) {
                $this->setMessage('error', implode('<br />', $action_results['error_messages']), false, null, false);
            }
        }

        $aliases = [];
        $accounts = [];
        if (!$verification_required) {
            $aliases = $this->getAliases($service, $package);
            $accounts = $this->getAccounts($service, $package, 0, 9999);
        }

        $alias_limit = $this->getAliasLimit($service, $package);

        $this->view->set('module_row', $module_row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('aliases', is_array($aliases) ? $aliases : []);
        $this->view->set('accounts', is_array($accounts) ? $accounts : []);
        $this->view->set('alias_limit', $alias_limit);
        $this->view->set('service_domain', $domain);
        $this->view->set('success_messages', []);
        $this->view->set('error_messages', []);
        $this->view->set('add_values', $action_results['add_values']);
        $this->view->set('verification_required', $verification_required);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('pending_manage_uri', $this->getServiceManageUri($service, 'tabClientAliases'));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Forwards tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientForwards($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_forwards', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        $context = $this->buildDkimContext($module_row, $domain, ['include_dkim' => false]);
        $verification_required = !empty($context['verification_required']);

        $action_results = [
            'success_messages' => [],
            'error_messages' => [],
            'add_values' => []
        ];

        if (!$verification_required) {
            $action_results = $this->processClientForwardActions(
                $module_row,
                $package,
                $service,
                $domain,
                $post ?? []
            );

            if (!empty($action_results['success_messages'])) {
                $this->setMessage('success', implode('<br />', $action_results['success_messages']), false, null, false);
            }
            if (!empty($action_results['error_messages'])) {
                $this->setMessage('error', implode('<br />', $action_results['error_messages']), false, null, false);
            }
        }

        $forwards = [];
        $accounts = [];
        if (!$verification_required) {
            $forwards = $this->getForwards($service, $package);
            $accounts = $this->getAccounts($service, $package, 0, 9999);
        }

        $this->view->set('module_row', $module_row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('forwards', is_array($forwards) ? $forwards : []);
        $this->view->set('accounts', is_array($accounts) ? $accounts : []);
        $this->view->set('service_domain', $domain);
        $this->view->set('success_messages', []);
        $this->view->set('error_messages', []);
        $this->view->set('add_values', $action_results['add_values']);
        $this->view->set('verification_required', $verification_required);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('pending_manage_uri', $this->getServiceManageUri($service, 'tabClientForwards'));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);
        return $this->view->fetch();
    }

    /**
     * Client Distribution Lists tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientLists($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_lists', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        $context = $this->buildDkimContext($module_row, $domain, ['include_dkim' => false]);
        $verification_required = !empty($context['verification_required']);

        $action_results = [
            'success_messages' => [],
            'error_messages' => [],
            'add_values' => [],
            'edit_values' => [],
            'edit_list_name' => '',
            'edit_members' => []
        ];

        if (!$verification_required) {
            $action_results = $this->processClientListActions(
                $module_row,
                $package,
                $service,
                $domain,
                $post ?? []
            );

            if (!empty($action_results['success_messages'])) {
                $this->setMessage('success', implode('<br />', $action_results['success_messages']), false, null, false);
            }
            if (!empty($action_results['error_messages'])) {
                $this->setMessage('error', implode('<br />', $action_results['error_messages']), false, null, false);
            }
        }

        $lists = [];
        $accounts = [];
        $list_members = [];
        if (!$verification_required) {
            $lists = $this->getLists($service, $package);
            $accounts = $this->getAccounts($service, $package, 0, 9999);
            if (!empty($action_results['edit_list_name'])) {
                $list_members = $this->getListMembers($service, $package, $action_results['edit_list_name']);
            }
        }

        $list_limit = $this->getListLimit($service, $package);

        $this->view->set('module_row', $module_row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('lists', is_array($lists) ? $lists : []);
        $this->view->set('accounts', is_array($accounts) ? $accounts : []);
        $this->view->set('list_members', is_array($list_members) ? $list_members : []);
        $this->view->set('list_limit', $list_limit);
        $this->view->set('service_domain', $domain);
        $this->view->set('success_messages', []);
        $this->view->set('error_messages', []);
        $this->view->set('add_values', $action_results['add_values']);
        $this->view->set('edit_values', $action_results['edit_values']);
        $this->view->set('edit_list_name', $action_results['edit_list_name']);
        $this->view->set('edit_members', $action_results['edit_members']);
        $this->view->set('verification_required', $verification_required);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('pending_manage_uri', $this->getServiceManageUri($service, 'tabClientLists'));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);
        return $this->view->fetch();
    }

    /**
     * Client DKIM tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientDkim($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_dkim', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $domain = $service_fields->polarismail_domain ?? '';
        $context = $this->buildDkimContext(
            $module_row,
            $domain,
            [
                'action' => $post['action'] ?? null,
                'include_dkim' => true
            ]
        );

        $this->view->set('module_row', $module_row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('dkim_status', $context['dkim_status']);
        $this->view->set('notice', $context['notice']);
        $this->view->set('domain', $domain);
        $this->view->set('verification_required', $context['verification_required']);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('pending_manage_uri', $this->getServiceManageUri($service, 'tabClientDkim'));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);
        return $this->view->fetch();
    }

    /**
     * Builds the context required to render DKIM controls and domain verification messaging.
     *
     * @param stdClass $module_row The module row being used for the service
     * @param string $domain The domain name attached to the service
     * @param array $options An array of options including:
     *  - action The DKIM toggle action to perform (enable|disable)
     *  - include_dkim Whether to fetch DKIM status information (default true)
     * @return array
     */
    private function buildDkimContext($module_row, $domain, array $options = [])
    {
        $options = array_merge(
            [
                'action' => null,
                'include_dkim' => true
            ],
            $options
        );

        $dkim_status = (object) [
            'enabled' => false,
            'host' => '',
            'key' => '',
            'has_details' => false,
            'status_text' => Language::_('Polarismail.tab_client_dkim.text_disabled', true)
        ];
        $notice = null;
        $verification_required = false;
        $verification_details = null;

        if (!$module_row || empty($domain)) {
            return [
                'dkim_status' => $dkim_status,
                'notice' => [
                    'type' => 'danger',
                    'message' => Language::_('Polarismail.!error.api.internal', true)
                ],
                'verification_required' => false,
                'verification_details' => null
            ];
        }

        try {
            $api = $this->getApi($module_row->meta->username, $module_row->meta->password);

            $token_response = $this->apiRequest($api, 'login');
            if (!$this->isApiSuccess($token_response) || empty($token_response->returndata)) {
                throw new Exception($this->getApiErrorMessage($token_response));
            }

            $token = $token_response->returndata;

            $verification_response = $this->apiRequest($api, 'getDomainVerification', [$token, $domain]);
            if (is_object($verification_response) && !empty($verification_response->returncode)) {
                $verification_required = true;
                $verification_details = (object) [
                    'value' => $verification_response->returndata ?? '',
                    'message' => $verification_response->message ?? ''
                ];

                // If user explicitly requested a recheck, show appropriate message
                if (!empty($options['action']) && $options['action'] === 'recheck') {
                    $notice = [
                        'type' => 'warning',
                        'message' => Language::_('Polarismail.!error.verification.still_pending', true)
                    ];
                }
            } else {
                // Verification is complete
                if (!empty($options['action']) && $options['action'] === 'recheck') {
                    $notice = [
                        'type' => 'success',
                        'message' => Language::_('Polarismail.!success.verification.complete', true)
                    ];
                }
            }

            if ($verification_required || !$options['include_dkim']) {
                return [
                    'dkim_status' => $dkim_status,
                    'notice' => $notice,
                    'verification_required' => $verification_required,
                    'verification_details' => $verification_details
                ];
            }

            if (!empty($options['action'])) {
                if ($options['action'] === 'enable') {
                    $enable_response = $this->apiRequest($api, 'enableDomainDKIM', [$token, $domain]);
                    if ($this->isApiSuccess($enable_response)) {
                        $notice = [
                            'type' => 'success',
                            'message' => $this->getApiResponseMessage(
                                $enable_response,
                                'Polarismail.!success.dkim_enabled'
                            )
                        ];
                    } else {
                        $notice = [
                            'type' => 'danger',
                            'message' => $this->getApiErrorMessage($enable_response)
                        ];
                    }
                } elseif ($options['action'] === 'disable') {
                    $disable_response = $this->apiRequest($api, 'disableDomainDKIM', [$token, $domain]);
                    if ($this->isApiSuccess($disable_response)) {
                        $notice = [
                            'type' => 'success',
                            'message' => $this->getApiResponseMessage(
                                $disable_response,
                                'Polarismail.!success.dkim_disabled'
                            )
                        ];
                    } else {
                        $notice = [
                            'type' => 'danger',
                            'message' => $this->getApiErrorMessage($disable_response)
                        ];
                    }
                }
            }

            $status_response = $this->apiRequest($api, 'getDomainDKIM', [$token, $domain]);
            $status_payload = $status_response->returndata ?? null;

            if (is_object($status_payload)) {
                $dkim_status->host = $status_payload->dkim_host ?? '';
                $dkim_status->key = $status_payload->dkim_key ?? '';
                $dkim_status->has_details = ($dkim_status->host !== '' || $dkim_status->key !== '');
            }

            if ($this->isApiSuccess($status_response) && is_object($status_payload)) {
                $dkim_enabled = !empty($status_payload->dkim_enabled);
                $dkim_status->enabled = $dkim_enabled;
                if ($dkim_enabled) {
                    $dkim_status->status_text = Language::_('Polarismail.tab_client_dkim.text_enabled', true);
                } elseif ($dkim_status->has_details) {
                    $dkim_status->status_text = Language::_('Polarismail.tab_client_dkim.text_pending', true);
                } else {
                    $dkim_status->status_text = Language::_('Polarismail.tab_client_dkim.text_disabled', true);
                }
            } elseif ($dkim_status->has_details && !$dkim_status->enabled) {
                $dkim_status->status_text = Language::_('Polarismail.tab_client_dkim.text_pending', true);
            } elseif (!$notice && $status_response && !$this->isApiSuccess($status_response)) {
                $notice = [
                    'type' => 'danger',
                    'message' => $this->getApiErrorMessage($status_response)
                ];
            }
        } catch (Exception $e) {
            $this->log('DKIM', $e->getMessage(), 'output', false);
            $notice = [
                'type' => 'danger',
                'message' => Language::_('Polarismail.!error.api.internal', true)
            ];
        }

        return [
            'dkim_status' => $dkim_status,
            'notice' => $notice,
            'verification_required' => $verification_required,
            'verification_details' => $verification_details
        ];
    }

    /**
     * Client Branding tab
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientBranding($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_branding', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $module_row = $this->getModuleRow($package->module_row ?? null);
        $service_fields = $this->serviceFieldsToObject($service->fields ?? []);
        $domain = $service_fields->polarismail_domain ?? '';

        $context = $this->buildDkimContext($module_row, $domain, ['include_dkim' => false]);
        $verification_required = !empty($context['verification_required']);

        $action_results = [
            'success_messages' => [],
            'error_messages' => [],
            'brand_values' => []
        ];

        if (!$verification_required) {
            $action_results = $this->processClientBrandingActions(
                $module_row,
                $domain,
                $post ?? [],
                $files ?? []
            );

            if (!empty($action_results['success_messages'])) {
                $this->setMessage('success', implode('<br />', $action_results['success_messages']), false, null, false);
            }
            if (!empty($action_results['error_messages'])) {
                $this->setMessage('error', implode('<br />', $action_results['error_messages']), false, null, false);
            }
        }

        $brand_info = null;
        if (!$verification_required) {
            $api = $this->getApi($module_row->meta->username, $module_row->meta->password);
            try {
                $token_response = $this->apiRequest($api, 'login');
            } catch (Exception $e) {
                $token_response = null;
            }

            if ($this->isApiSuccess($token_response) && !empty($token_response->returndata)) {
                try {
                    $brand_response = $this->apiRequest($api, 'getDomainBrandInfo', [$token_response->returndata, $domain]);
                } catch (Exception $e) {
                    $brand_response = null;
                }

                if ($this->isApiSuccess($brand_response) && isset($brand_response->returndata)) {
                    $brand_info = $brand_response->returndata;
                }
            }
        }

        $this->view->set('module_row', $module_row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $service_fields);
        $this->view->set('brand_info', $brand_info);
        $this->view->set('service_domain', $domain);
        $this->view->set('success_messages', []);
        $this->view->set('error_messages', []);
        $this->view->set('brand_values', $action_results['brand_values']);
        $this->view->set('verification_required', $verification_required);
        $this->view->set('verification_details', $context['verification_details']);
        $this->view->set('pending_manage_uri', $this->getServiceManageUri($service, 'tabClientBranding'));

        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'polarismail' . DS);
        return $this->view->fetch();
    }

    /**
     * Performs module installation
     * Creates configurable option groups and options for PolarisMail packages
     *
     * @param int $module_id The ID of the module being installed
     */
    public function install($module_id = null)
    {
        // Load required models
        Loader::loadModels($this, ['PackageOptionGroups', 'PackageOptions']);

        // Get the company ID from the current configuration
        $company_id = Configure::get('Blesta.company_id');

        // Create the PolarisMail Options package option group
        $group_vars = [
            'company_id' => $company_id,
            'name' => 'PolarisMail Options',
            'description' => 'Mailbox, storage, aliases and lists for PolarisMail email hosting'
        ];

        $group_id = $this->PackageOptionGroups->add($group_vars);

        // Check if group was created successfully
        if (($errors = $this->PackageOptionGroups->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        // Create configurable option: Basic Mailboxes
        $option1_vars = [
            'company_id' => $company_id,
            'label' => 'Basic Mailboxes',
            'name' => 'basic_mailboxes',
            'type' => 'quantity',
            'addable' => 1,
            'editable' => 1,
            'groups' => [$group_id],
            'values' => [
                [
                    'name' => 'Basic Mailboxes',
                    'value' => null,
                    'min' => null,
                    'max' => null,
                    'step' => '1',
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 1.50,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ]
            ]
        ];

        $option1_id = $this->PackageOptions->add($option1_vars);

        if (($errors = $this->PackageOptions->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        // Create configurable option: Enhanced Mailboxes
        $option2_vars = [
            'company_id' => $company_id,
            'label' => 'Enhanced Mailboxes',
            'name' => 'enhanced_mailboxes',
            'type' => 'quantity',
            'addable' => 1,
            'editable' => 1,
            'groups' => [$group_id],
            'values' => [
                [
                    'name' => 'Enhanced Mailboxes',
                    'value' => null,
                    'min' => null,
                    'max' => null,
                    'step' => '1',
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 3.00,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ]
            ]
        ];

        $option2_id = $this->PackageOptions->add($option2_vars);

        if (($errors = $this->PackageOptions->errors())) {
            $this->Input->setErrors($errors);
            return;
        }

        // Create configurable option: Mailbox Quota (GB) - Dropdown
        $option3_vars = [
            'company_id' => $company_id,
            'label' => 'Mailbox Quota (GB)',
            'name' => 'mailbox_quota_gb',
            'type' => 'select',
            'addable' => 1,
            'editable' => 1,
            'groups' => [$group_id],
            'values' => [
                [
                    'name' => '5 GB',
                    'value' => '5',
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 0.00,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ],
                [
                    'name' => '10 GB',
                    'value' => '10',
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 1.00,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ],
                [
                    'name' => '25 GB',
                    'value' => '25',
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 2.00,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ],
                [
                    'name' => '50 GB',
                    'value' => '50',
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 4.00,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ],
                [
                    'name' => '100 GB',
                    'value' => '100',
                    'min' => null,
                    'max' => null,
                    'step' => null,
                    'pricing' => [
                        [
                            'term' => 1,
                            'period' => 'month',
                            'price' => 7.00,
                            'setup_fee' => 0.00,
                            'cancel_fee' => 0.00,
                            'currency' => 'USD'
                        ]
                    ]
                ]
            ]
        ];

        $option3_id = $this->PackageOptions->add($option3_vars);

        if (($errors = $this->PackageOptions->errors())) {
            $this->Input->setErrors($errors);
            return;
        }
    }

    /**
     * Performs module uninstallation
     * Attempts to remove the PolarisMail Options package option group and its options
     *
     * @param int $module_id The ID of the module being uninstalled
     * @param bool $last_instance Whether this is the last instance of the module
     */
    public function uninstall($module_id = null, $last_instance = false)
    {
        // Only remove package options if this is the last instance
        if (!$last_instance) {
            return;
        }

        // Load required models
        Loader::loadModels($this, ['PackageOptionGroups', 'PackageOptions']);

        // Get the company ID
        $company_id = Configure::get('Blesta.company_id');

        // Find the PolarisMail Options group
        $groups = $this->PackageOptionGroups->getAll($company_id);

        foreach ($groups as $group) {
            if ($group->name === 'PolarisMail Options') {
                // Get all options in this group
                $options = $this->PackageOptionGroups->getAllOptions($group->id);

                // Delete each option
                foreach ($options as $option) {
                    $this->PackageOptions->delete($option->id);
                }

                // Delete the group
                $this->PackageOptionGroups->delete($group->id);
                break;
            }
        }
    }
}

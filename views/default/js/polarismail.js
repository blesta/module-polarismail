/**
 * PolarisMail Module JavaScript
 */

(function() {
    'use strict';

    /**
     * Attach a delegated event handler to the document. Mirrors
     * jQuery's $(document).on(type, selector, handler) where "this"
     * inside the handler refers to the matched element.
     */
    function delegate(event_type, selector, handler) {
        document.addEventListener(event_type, function(event) {
            var element = event.target.closest(selector);

            if (element && document.contains(element)) {
                handler.call(element, event);
            }
        });
    }

    // Main module object
    var PolarisMail = {
        /**
         * Initialize the module
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Account management
            delegate('click', '#add-account', this.showAddAccountModal);
            delegate('click', '.edit-account', this.showEditAccountModal);
            delegate('click', '.delete-account', this.deleteAccount);
            delegate('click', '.enable-account', this.enableAccount);
            delegate('click', '.disable-account', this.disableAccount);
            delegate('click', '.webmail-login', this.loginWebmail);
            delegate('click', '.cp-login', this.loginControlPanel);

            // Alias management
            delegate('click', '#add-alias', this.showAddAliasModal);
            delegate('click', '.delete-alias', this.deleteAlias);

            // Forward management
            delegate('click', '#add-forward', this.showAddForwardModal);
            delegate('click', '.delete-forward', this.deleteForward);

            // Distribution list management
            delegate('click', '#add-list', this.showAddListModal);
            delegate('click', '.edit-list', this.showEditListModal);
            delegate('click', '.delete-list', this.deleteList);

            // Branding management
            delegate('click', '#reset-branding', this.resetBranding);

            // Utility actions
            delegate('click', '.copy-to-clipboard', function(event) {
                PolarisMail.copyToClipboard(event, this);
            });
        },

        /**
         * Show add account modal
         */
        showAddAccountModal: function(e) {
            e.preventDefault();
            // Implementation depends on your modal system
            console.log('Show add account modal');
        },

        /**
         * Show edit account modal
         */
        showEditAccountModal: function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');
            console.log('Show edit account modal for: ' + username);
        },

        /**
         * Delete account
         */
        deleteAccount: function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');

            if (confirm('Are you sure you want to delete this email account?')) {
                // Perform AJAX request to delete account
                console.log('Delete account: ' + username);
            }
        },

        /**
         * Enable account
         */
        enableAccount: function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');
            console.log('Enable account: ' + username);
        },

        /**
         * Disable account
         */
        disableAccount: function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');

            if (confirm('Are you sure you want to disable this email account?')) {
                console.log('Disable account: ' + username);
            }
        },

        /**
         * Login to webmail
         */
        loginWebmail: function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');
            console.log('Login to webmail: ' + username);
        },

        /**
         * Login to control panel
         */
        loginControlPanel: function(e) {
            e.preventDefault();
            var username = this.getAttribute('data-username');
            console.log('Login to control panel: ' + username);
        },

        /**
         * Show add alias modal
         */
        showAddAliasModal: function(e) {
            e.preventDefault();
            console.log('Show add alias modal');
        },

        /**
         * Delete alias
         */
        deleteAlias: function(e) {
            e.preventDefault();
            var alias = this.getAttribute('data-alias');

            if (confirm('Are you sure you want to delete this alias?')) {
                console.log('Delete alias: ' + alias);
            }
        },

        /**
         * Show add forward modal
         */
        showAddForwardModal: function(e) {
            e.preventDefault();
            console.log('Show add forward modal');
        },

        /**
         * Delete forward
         */
        deleteForward: function(e) {
            e.preventDefault();
            var forward = this.getAttribute('data-forward');

            if (confirm('Are you sure you want to delete this forward?')) {
                console.log('Delete forward: ' + forward);
            }
        },

        /**
         * Show add list modal
         */
        showAddListModal: function(e) {
            e.preventDefault();
            console.log('Show add list modal');
        },

        /**
         * Show edit list modal
         */
        showEditListModal: function(e) {
            e.preventDefault();
            var listname = this.getAttribute('data-listname');
            console.log('Show edit list modal for: ' + listname);
        },

        /**
         * Delete distribution list
         */
        deleteList: function(e) {
            e.preventDefault();
            var listname = this.getAttribute('data-listname');

            if (confirm('Are you sure you want to delete this distribution list?')) {
                console.log('Delete list: ' + listname);
            }
        },

        /**
         * Reset branding
         */
        resetBranding: function(e) {
            e.preventDefault();

            if (confirm('Are you sure you want to reset branding to default?')) {
                console.log('Reset branding');
            }
        },

        /**
         * Copy the value of a target element to the clipboard
         */
        copyToClipboard: function(event, trigger) {
            if (event) {
                event.preventDefault();
            }

            var source = trigger ? trigger : event.currentTarget;
            var target_selector = source.getAttribute('data-clipboard-target');
            if (!target_selector) {
                return;
            }

            var target = document.querySelector(target_selector);
            if (!target) {
                return;
            }

            var value = '';
            if (target.matches('input, textarea')) {
                value = target.value;
            } else {
                value = target.textContent;
            }

            var helper = document.createElement('textarea');
            helper.style.position = 'absolute';
            helper.style.left = '-10000px';
            helper.style.top = '0';
            helper.style.opacity = '0';

            document.body.appendChild(helper);
            helper.value = value;
            helper.select();

            try {
                document.execCommand('copy');
            } catch (error) {
                // Clipboard copy not supported
            }

            helper.parentNode.removeChild(helper);

            if (trigger) {
                trigger.blur();
            }
        }
    };

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            PolarisMail.init();
        });
    } else {
        PolarisMail.init();
    }

})();

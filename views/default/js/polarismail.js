/**
 * PolarisMail Module JavaScript
 */

(function($) {
    'use strict';

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
            $(document).on('click', '#add-account', this.showAddAccountModal);
            $(document).on('click', '.edit-account', this.showEditAccountModal);
            $(document).on('click', '.delete-account', this.deleteAccount);
            $(document).on('click', '.enable-account', this.enableAccount);
            $(document).on('click', '.disable-account', this.disableAccount);
            $(document).on('click', '.webmail-login', this.loginWebmail);
            $(document).on('click', '.cp-login', this.loginControlPanel);

            // Alias management
            $(document).on('click', '#add-alias', this.showAddAliasModal);
            $(document).on('click', '.delete-alias', this.deleteAlias);

            // Forward management
            $(document).on('click', '#add-forward', this.showAddForwardModal);
            $(document).on('click', '.delete-forward', this.deleteForward);

            // Distribution list management
            $(document).on('click', '#add-list', this.showAddListModal);
            $(document).on('click', '.edit-list', this.showEditListModal);
            $(document).on('click', '.delete-list', this.deleteList);

            // Branding management
            $(document).on('click', '#reset-branding', this.resetBranding);

            // Utility actions
            $(document).on('click', '.copy-to-clipboard', function(event) {
                PolarisMail.copyToClipboard(event, $(this));
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
            var username = $(this).data('username');
            console.log('Show edit account modal for: ' + username);
        },

        /**
         * Delete account
         */
        deleteAccount: function(e) {
            e.preventDefault();
            var username = $(this).data('username');

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
            var username = $(this).data('username');
            console.log('Enable account: ' + username);
        },

        /**
         * Disable account
         */
        disableAccount: function(e) {
            e.preventDefault();
            var username = $(this).data('username');

            if (confirm('Are you sure you want to disable this email account?')) {
                console.log('Disable account: ' + username);
            }
        },

        /**
         * Login to webmail
         */
        loginWebmail: function(e) {
            e.preventDefault();
            var username = $(this).data('username');
            console.log('Login to webmail: ' + username);
        },

        /**
         * Login to control panel
         */
        loginControlPanel: function(e) {
            e.preventDefault();
            var username = $(this).data('username');
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
            var alias = $(this).data('alias');

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
            var forward = $(this).data('forward');

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
            var listname = $(this).data('listname');
            console.log('Show edit list modal for: ' + listname);
        },

        /**
         * Delete distribution list
         */
        deleteList: function(e) {
            e.preventDefault();
            var listname = $(this).data('listname');

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
        copyToClipboard: function(event, $trigger) {
            if (event) {
                event.preventDefault();
            }

            var $source = $trigger && $trigger.length ? $trigger : $(event.currentTarget);
            var targetSelector = $source.data('clipboard-target');
            if (!targetSelector) {
                return;
            }

            var $target = $(targetSelector);
            if (!$target.length) {
                return;
            }

            var value = '';
            if ($target.is('input, textarea')) {
                value = $target.val();
            } else {
                value = $target.text();
            }

            var $helper = $('<textarea>').css({
                position: 'absolute',
                left: '-10000px',
                top: '0',
                opacity: '0'
            });

            $('body').append($helper);
            $helper.val(value).select();

            try {
                document.execCommand('copy');
            } catch (error) {
                // Clipboard copy not supported
            }

            $helper.remove();

            if ($trigger && $trigger.length) {
                $trigger.blur();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PolarisMail.init();
    });

})(jQuery);

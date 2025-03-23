(function($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    /**
     * Módulo de pestañas
     */
    EnglishLineTest.Tabs = {
        /**
         * Inicializa el sistema de pestañas
         */
        init: function() {
            this.bindEvents();
            this.restoreActiveTab();
        },

        /**
         * Vincula los eventos necesarios para el funcionamiento de las pestañas
         */
        bindEvents: function() {
            let self = this;
            
            // Manejar clic en pestañas
            $('.englishline-tabs-nav .tab-link').on('click', function(e) {
                e.preventDefault();
                self.switchTab($(this));
            });
        },

        /**
         * Cambia a la pestaña seleccionada
         * 
         * @param {jQuery} $tabLink - El enlace de pestaña seleccionado
         */
        switchTab: function($tabLink) {
            let tabId = $tabLink.data('tab');
            let $tabsContainer = $tabLink.closest('.englishline-admin-wrap');
            
            // Actilet enlace de pestaña
            $tabLink.siblings().removeClass('active');
            $tabLink.addClass('active');
            
            // Mostrar contenido de pestaña
            $tabsContainer.find('.englishline-tab-content').removeClass('active');
            $tabsContainer.find('#tab-' + tabId).addClass('active');
            
            // Guardar estado de pestaña en localStorage
            if ($tabLink.closest('.englishline-tabs-nav').data('remember-tab')) {
                this.saveActiveTab(tabId);
            }

            // Disparar evento para notificar el cambio de pestaña
            $(document).trigger('englishline_tab_switched', [tabId, $tabLink]);
        },

        /**
         * Guarda la pestaña activa en localStorage
         * 
         * @param {string} tabId - ID de la pestaña activa
         */
        saveActiveTab: function(tabId) {
            if (typeof Storage !== "undefined") {
                let pageId = this.getPageId();
                localStorage.setItem('englishline_active_tab_' + pageId, tabId);
            }
        },

        /**
         * Restaura la última pestaña activa desde localStorage
         */
        restoreActiveTab: function() {
            if (typeof Storage !== "undefined") {
                let pageId = this.getPageId();
                let lastTab = localStorage.getItem('englishline_active_tab_' + pageId);
                
                if (lastTab) {
                    let $tabLink = $('.englishline-tabs-nav .tab-link[data-tab="' + lastTab + '"]');
                    if ($tabLink.length) {
                        $tabLink.trigger('click');
                    }
                }
            }
        },

        /**
         * Obtiene un identificador único para la página actual
         * 
         * @return {string} ID de la página
         */
        getPageId: function() {
            // Obtener el slug de la página de la URL
            let urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('page') || 'default';
        }
    };

})(jQuery);
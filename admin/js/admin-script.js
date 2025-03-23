(function($) {
    'use strict';

    // Espacio de nombres principal para el plugin
    window.EnglishLineTest = window.EnglishLineTest || {};

    $(function() {
        // Inicializar módulos cuando se carga el documento
        
        // Inicializar sistema de pestañas en todas las páginas
        if (typeof EnglishLineTest.Tabs !== 'undefined') {
            EnglishLineTest.Tabs.init();
        }

        // Inicializar el constructor de formularios
        if ($('#form-builder').length && typeof EnglishLineTest.FormBuilder !== 'undefined') {
            // EnglishLineTest.FormBuilder.init();
        }

        // Inicializar la vista previa de formularios
        if ($('#form-preview').length && typeof EnglishLineTest.FormPreview !== 'undefined') {
            EnglishLineTest.FormPreview.init();
        }

        //  Inicializar la tabla de resultados
        if ($('.englishline-results-table').length && typeof EnglishLineTest.Results !== 'undefined') {
            EnglishLineTest.Results.init();
        }

        //  Inicializar la página de calificación
        if ($('#englishline-grade-form').length && typeof EnglishLineTest.Grading !== 'undefined') {
            EnglishLineTest.Grading.init();
        }

        //  Inicializar la página de configuración
        if ($('.englishline-admin-wrap .englishline-tabs-nav').length && typeof EnglishLineTest.Settings !== 'undefined') {
            EnglishLineTest.Settings.init();
        }
    });


    window.initializeFormWithData = function(formData) {
        if (typeof EnglishLineTest.FormBuilder !== 'undefined' && typeof EnglishLineTest.FormBuilder.loadFormData === 'function') {
            EnglishLineTest.FormBuilder.loadFormData(formData);
        }
    };

})(jQuery);
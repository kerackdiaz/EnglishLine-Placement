(function($) {
    'use strict';
    
    // Crear objeto global para helpers de recuperación
    window.EnglishLineDebugHelper = {
        recoverFormData: function() {
            console.log("Intentando recuperar datos del formulario...");
            
            // Intentar varias formas de obtener los datos
            let rawData = null;
            
            if (typeof formDataFromPHP !== 'undefined') {
                rawData = formDataFromPHP;
                console.log("Datos encontrados en formDataFromPHP");
            }
            
            if (!rawData && typeof window._backupFormData !== 'undefined') {
                rawData = window._backupFormData;
                console.log("Datos encontrados en _backupFormData");
            }
            
            if (!rawData) {
                console.error("No se encontraron datos para recuperar");
                return false;
            }
            
            // Intentar procesar los datos
            let processedData = null;
            
            try {
                // Si ya es un array, usarlo directamente
                if (Array.isArray(rawData)) {
                    processedData = rawData;
                }
                // Si es un string, intentar parsearlo
                else if (typeof rawData === 'string') {
                    // Limpiar posibles escapes
                    let cleanedData = rawData
                        .replace(/\\\\/g, '\\')
                        .replace(/\\"/g, '"')
                        .replace(/^"/, '')
                        .replace(/"$/, '');
                    
                    processedData = JSON.parse(cleanedData);
                }
                else {
                    console.error("Formato de datos desconocido");
                    return false;
                }
                
                // Verificar resultado
                if (!Array.isArray(processedData)) {
                    console.error("Los datos procesados no son un array:", processedData);
                    return false;
                }
                
                console.log("Datos procesados:", processedData);
                
                // Asignar al objeto FormData
                EnglishLineTest.FormData.sectionsData = processedData;
                
                // Renderizar
                if (typeof EnglishLineTest.FormBuilder !== 'undefined') {
                    EnglishLineTest.FormBuilder.renderSections();
                }
                
                return true;
            } catch (e) {
                console.error("Error procesando datos:", e);
                return false;
            }
        },
        
        reinicializarOpciones: function() {
            console.log("Reinicializando todas las opciones...");
            
            // Para cada pregunta del tipo adecuado
            $('.form-question[data-question-type="select"], .form-question[data-question-type="radio"], .form-question[data-question-type="checkbox"]').each(function() {
                let $question = $(this);
                let sectionIndex = $question.closest('.form-section').data('section-index');
                let questionIndex = $question.data('question-index');
                
                // Forzar edición para mostrar el panel
                $question.addClass('editing');
                $question.find('.form-question-config').show();
                
                // Inicializar opciones
                EnglishLineTest.FormEvents.initOptionsForQuestion($question, sectionIndex, questionIndex);
            });
            
            return "Reinicialización completa.";
        }
    };
    
    // Agregar a window.console para acceso más fácil
    window.console.recoverForm = window.EnglishLineDebugHelper.recoverFormData;
    window.console.reiniciarOpciones = window.EnglishLineDebugHelper.reinicializarOpciones;
    
    // Backup de datos del formulario
    $(document).ready(function() {
        setTimeout(function() {
            if (typeof formDataFromPHP !== 'undefined') {
                window._backupFormData = formDataFromPHP;
                console.log("Datos de formulario respaldados");
            }
        }, 1000);
    });
    
})(jQuery);
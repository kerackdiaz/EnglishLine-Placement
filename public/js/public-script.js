/**
 * JavaScript para el área pública del plugin EnglishLine Test
 */

jQuery(document).ready(function($) {

    const sectionTimers = {};
    const timedOutSections = new Set();

    // Declarar las funciones del temporizador antes de usarlas
    
    /**
     * Detiene el temporizador para una sección
     */
    function stopSectionTimer(sectionIndex) {
        if (sectionTimers[sectionIndex]) {
            clearInterval(sectionTimers[sectionIndex]);
            sectionTimers[sectionIndex] = null;
        }
    }
    
    /**
     * Actualiza la visualización del temporizador
     */
    function updateTimerDisplay($timer, seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        
        // Formatear con ceros a la izquierda
        const display = 
            (mins < 10 ? '0' : '') + mins + ':' + 
            (secs < 10 ? '0' : '') + secs;
        
        $timer.text(display);
        
        // Aplicar estilo de advertencia cuando quede poco tiempo
        if (seconds <= 60) {
            $timer.addClass('time-warning');
        } else {
            $timer.removeClass('time-warning');
        }
    }
    
    /**
     * Inicializa el temporizador para una sección específica
     */
    function initSectionTimer(sectionIndex) {
        // Buscar el elemento de temporizador en esta sección
        const $timer = $(`.englishline-timer[data-section="${sectionIndex}"]`);
        if (!$timer.length) return;
        
        // Obtener el número de minutos del atributo data
        const minutes = parseInt($timer.data('minutes')) || 0;
        if (minutes <= 0) return;
        

        
        // Detener temporizador existente si lo hay
        stopSectionTimer(sectionIndex);
        
        // Calcular tiempo total en segundos
        let totalSeconds = minutes * 60;
        
        // Actualizar visualización inicial
        updateTimerDisplay($timer, totalSeconds);
        
        // Iniciar temporizador
        sectionTimers[sectionIndex] = setInterval(function() {
            totalSeconds--;
            
            // Actualizar visualización
            updateTimerDisplay($timer, totalSeconds);
            
            // Si se agotó el tiempo
            if (totalSeconds <= 0) {
                stopSectionTimer(sectionIndex);
                timedOutSections.add(sectionIndex);
                
                // Mostrar mensaje
                alert('¡El tiempo para esta sección ha terminado!');
                
                // Avanzar automáticamente
                const $nextBtn = $('.englishline-form-next:visible');
                if ($nextBtn.length) {
                    $nextBtn.trigger('click');
                } else {
                    // Si es la última sección, enviar formulario
                    $('.englishline-form-submit:visible').trigger('click');
                }
            }
        }, 1000);
    }

    // Inicializar los formularios multi-pasos
    initMultiStepForms();

    /**
     * Inicializa los formularios multi-pasos
     */
    function initMultiStepForms() {
        $('.englishline-form-container').each(function() {
            let $form = $(this);
            let $formElement = $form.find('form.englishline-test-form');
            let $steps = $form.find('.englishline-form-step-content');
            let $indicators = $form.find('.englishline-form-step');
            let currentStep = 0;
            let totalSteps = $steps.length;

            // Ocultar todos los pasos excepto el primero
            $steps.not(':first').hide();

            // Actualizar indicadores de pasos iniciales
            updateStepIndicators();

            // Iniciar temporizador para la primera sección si tiene uno
            initSectionTimer(0);

            // Manejar clic en botón siguiente
            $form.on('click', '.englishline-form-next', function(e) {
                e.preventDefault();

                // Detener el temporizador actual
                stopSectionTimer(currentStep);

                // Si estamos en el último paso (paso de contacto), este botón funciona como enviar
                if (currentStep === totalSteps - 1) {
                    if (validateStep(currentStep)) {
                        submitForm();
                    }
                } else {
                    // Validar el paso actual antes de avanzar
                    if (validateStep(currentStep)) {
                        currentStep++;
                        showStep(currentStep);
                        updateStepIndicators();
                    }
                }
            });

            // Manejar clic en botón anterior
            $form.on('click', '.englishline-form-prev', function(e) {
                e.preventDefault();

                
                // Verificar si la sección anterior tiene tiempo agotado
                const targetStep = currentStep - 1;
                if (timedOutSections.has(targetStep)) {
                    alert('No es posible regresar a una sección anterior cuyo tiempo ha finalizado.');
                    return false;
                }
                
                // Detener el temporizador actual
                stopSectionTimer(currentStep);
                
                currentStep--;
                showStep(currentStep);
                updateStepIndicators();
            });

            // Manejar clic en botón enviar específico
            $form.on('click', '.englishline-form-submit', function(e) {
                e.preventDefault();
                if (validateStep(currentStep)) {
                    submitForm();
                }
            });

            /**
             * Muestra un paso específico del formulario
             */
            function showStep(stepIndex) {
                $steps.hide();
                $steps.eq(stepIndex).show();

                // Actualizar botones de navegación
                let $prevBtn = $form.find('.englishline-form-prev');
                let $nextBtn = $form.find('.englishline-form-next');
                let $submitBtn = $form.find('.englishline-form-submit');

                if (stepIndex === 0) {
                    $prevBtn.hide();
                } else {
                    $prevBtn.show();
                }

                // Si estamos en el último paso, mostrar botón de envío
                if (stepIndex === totalSteps - 1) {
                    $nextBtn.hide();
                    $submitBtn.show();
                } else {
                    $submitBtn.hide();
                    $nextBtn.show();
                    $nextBtn.find('.button-text').text('Siguiente');
                    $nextBtn.find('.button-icon i').removeClass('dashicons-yes').addClass('dashicons-arrow-right-alt');
                }

                // Desplazarse al inicio del formulario
                $('html, body').animate({
                    scrollTop: $form.offset().top - 50
                }, 300);

                // Iniciar temporizador para esta sección
                initSectionTimer(stepIndex);
            }

            /**
             * Actualiza los indicadores de pasos
             */
            function updateStepIndicators() {
                $indicators.each(function(i) {
                    if (i < currentStep) {
                        $(this).removeClass('active').addClass('completed');
                    } else if (i === currentStep) {
                        $(this).addClass('active').removeClass('completed');
                    } else {
                        $(this).removeClass('active completed');
                    }
                });
            }

            /**
             * Valida el paso actual
             */
            function validateStep(stepIndex) {
                let $currentStep = $steps.eq(stepIndex);
                let valid = true;

                // Validar campos requeridos
                $currentStep.find('[required]').each(function() {
                    if (!$(this).val()) {
                        valid = false;
                        $(this).addClass('error');

                        // Mostrar mensaje de error
                        if (!$(this).next('.error-message').length) {
                            $(this).after('<span class="error-message">Este campo es obligatorio</span>');
                        }
                    } else {
                        $(this).removeClass('error');
                        $(this).next('.error-message').remove();
                    }
                });

                // Si hay errores, mostrar mensaje general
                if (!valid) {
                    if (!$currentStep.find('.step-error-message').length) {
                        $currentStep.prepend('<div class="step-error-message">Por favor, completa todos los campos requeridos.</div>');

                        // Quitar el mensaje después de un tiempo
                        setTimeout(function() {
                            $currentStep.find('.step-error-message').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                }

                return valid;
            }

            /**
             * Envía el formulario
             */
            function submitForm() {
                // Mostrar indicador de carga
                let $submitBtn = $form.find('.englishline-form-submit');
                let submitBtnText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Enviando...');
                $form.addClass('submitting');
                $form.find('.englishline-loader').show();
                $form.find('.englishline-form-error').hide();

                // Preparar datos del formulario
                let formData = new FormData($formElement[0]);
                
                // Añadir datos adicionales necesarios
                formData.append('action', 'englishline_form_submit'); 
                formData.append('nonce', englishline_test.nonce);
                formData.append('form_id', $form.data('form-id'));


                // Enviar mediante Ajax
                $.ajax({
                    url: englishline_test.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $form.removeClass('submitting');
                        $form.find('.englishline-loader').hide();

                        if (response.success) {
                            // Ocultar formulario y mostrar mensaje de éxito
                            $formElement.hide();
                            $form.find('.englishline-form-success')
                                .html('<h3>¡Gracias por completar el test!</h3>' +
                                    '<p>' + response.data.message + '</p>')
                                .show();

                            // Si hay una redirección configurada
                            if (response.data && response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 2000);
                            }
                        } else {
                            // Mostrar error
                            $submitBtn.prop('disabled', false).text(submitBtnText);
                            $form.find('.englishline-form-error')
                                .text(response.data && response.data.message ? response.data.message : 'Ha ocurrido un error al procesar tu formulario.')
                                .show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', error);
                        console.error('Estado:', status);
                        console.error('Respuesta:', xhr.responseText);
                        
                        $form.removeClass('submitting');
                        $form.find('.englishline-loader').hide();

                        // Mostrar error
                        $submitBtn.prop('disabled', false).text(submitBtnText);
                        $form.find('.englishline-form-error')
                            .text('Error de conexión. Por favor, intenta nuevamente más tarde.')
                            .show();
                    }
                });
            }
        });
    }

    // Inicializar eventos para validación en tiempo real
    initInputValidation();

    /**
     * Inicializa la validación en tiempo real para los campos
     */
    function initInputValidation() {
        $('.englishline-form-container').on('blur', 'input[required], select[required], textarea[required]', function() {
            let $field = $(this);

            // Validar al perder el foco
            if (!$field.val()) {
                $field.addClass('error');

                // Mostrar mensaje de error si no existe
                if (!$field.next('.error-message').length) {
                    $field.after('<span class="error-message">Este campo es obligatorio</span>');
                }
            } else {
                $field.removeClass('error');
                $field.next('.error-message').remove();
            }
        });

        // Validar email en tiempo real
        $('.englishline-form-container').on('blur', 'input[type="email"]', function() {
            let $field = $(this);
            let email = $field.val();

            if (email && !validateEmail(email)) {
                $field.addClass('error');

                // Mostrar mensaje de error
                if (!$field.next('.error-message').length) {
                    $field.after('<span class="error-message">Por favor, ingresa un email válido</span>');
                } else {
                    $field.next('.error-message').text('Por favor, ingresa un email válido');
                }
            }
        });
    }

    /**
     * Valida un formato de email
     */
    function validateEmail(email) {
        let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    // Expandir/contraer explicaciones de preguntas
    $('.englishline-form-container').on('click', '.question-explanation-toggle', function(e) {
        e.preventDefault();

        let $toggle = $(this);
        let $explanation = $toggle.next('.question-explanation-content');

        $explanation.slideToggle(200);
        $toggle.toggleClass('active');

        if ($toggle.hasClass('active')) {
            $toggle.text('Ocultar explicación');
        } else {
            $toggle.text('Ver explicación');
        }
    });

    // Inicializar contador de caracteres para campos de texto con límite
    $('.englishline-form-container').on('keyup', 'textarea[data-max-chars]', function() {
        let $textarea = $(this);
        let maxChars = parseInt($textarea.data('max-chars'));
        let currentChars = $textarea.val().length;
        let $counter = $textarea.next('.character-counter');

        if (!$counter.length) {
            $counter = $('<div class="character-counter"></div>');
            $textarea.after($counter);
        }

        $counter.text(currentChars + ' / ' + maxChars + ' caracteres');

        // Limitar caracteres si se excede
        if (currentChars > maxChars) {
            $textarea.val($textarea.val().substring(0, maxChars));
            $counter.text(maxChars + ' / ' + maxChars + ' caracteres');
        }

        // Cambiar color si se acerca al límite
        if (currentChars > maxChars * 0.9) {
            $counter.addClass('limit-near');
        } else {
            $counter.removeClass('limit-near');
        }
    });

    // Manejo de preguntas tipo ordenamiento (ordering)
    if ($.fn.sortable && $('.ordering-list').length > 0) {
        $('.ordering-list').sortable({
            handle: '.dashicons-menu',
            update: function(event, ui) {
                $(this).find('.ordering-item').each(function(index) {
                    const originalIndex = $(this).data('original-index');
                    $(this).find('input[type="hidden"]').val(originalIndex);
                });
            }
        });
    } else if ($('.ordering-list').length > 0) {
        console.warn('jQuery UI Sortable no está disponible para las preguntas de ordenamiento');
    }

    // Añadir estilos CSS para el temporizador
    const timerStyles = `
        .englishline-timer-container {
            background-color: #f0f0f0;
            border-radius: 4px;
            padding: 6px 12px;
            margin-bottom: 15px;
            display: inline-block;
            font-weight: bold;
        }
        
        .timer-label {
            margin-right: 8px;
        }
        
        .englishline-timer {
            font-family: monospace;
            font-size: 1.2em;
        }
        
        .time-warning {
            color: #cc0000;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    `;
    
    $('head').append('<style>' + timerStyles + '</style>');
});
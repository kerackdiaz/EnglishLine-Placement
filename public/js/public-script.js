jQuery(document).ready(function($) {
    const sectionTimers = {};
    const timedOutSections = new Set();
    
    function stopSectionTimer(sectionIndex) {
        if (sectionTimers[sectionIndex]) {
            clearInterval(sectionTimers[sectionIndex]);
            sectionTimers[sectionIndex] = null;
        }
    }
    
    function updateTimerDisplay($timer, seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        
        const display = 
            (mins < 10 ? '0' : '') + mins + ':' + 
            (secs < 10 ? '0' : '') + secs;
        
        $timer.text(display);
        
        if (seconds <= 60) {
            $timer.addClass('time-warning');
        } else {
            $timer.removeClass('time-warning');
        }
    }
    
    function initSectionTimer(sectionIndex) {
        const $timer = $(`.englishline-timer[data-section="${sectionIndex}"]`);
        if (!$timer.length) return;
        
        const minutes = parseInt($timer.data('minutes')) || 0;
        if (minutes <= 0) return;
        
        stopSectionTimer(sectionIndex);
        
        let totalSeconds = minutes * 60;
        
        updateTimerDisplay($timer, totalSeconds);
        
        sectionTimers[sectionIndex] = setInterval(function() {
            totalSeconds--;
            
            updateTimerDisplay($timer, totalSeconds);
            
            if (totalSeconds <= 0) {
                stopSectionTimer(sectionIndex);
                timedOutSections.add(sectionIndex);
                
                alert('¡El tiempo para esta sección ha terminado!');
                
                const $nextBtn = $('.englishline-form-next:visible');
                if ($nextBtn.length) {
                    $nextBtn.trigger('click');
                } else {
                    $('.englishline-form-submit:visible').trigger('click');
                }
            }
        }, 1000);
    }

    initMultiStepForms();

    function initMultiStepForms() {
        $('.englishline-form-container').each(function() {
            let $form = $(this);
            let $formElement = $form.find('form.englishline-test-form');
            let $steps = $form.find('.englishline-form-step-content');
            let $indicators = $form.find('.englishline-form-step');
            let currentStep = 0;
            let totalSteps = $steps.length;

            $steps.not(':first').hide();
            updateStepIndicators();
            initSectionTimer(0);

            $form.on('click', '.englishline-form-next', function(e) {
                e.preventDefault();

                stopSectionTimer(currentStep);

                if (currentStep === totalSteps - 1) {
                    if (validateStep(currentStep)) {
                        submitForm();
                    }
                } else {
                    if (validateStep(currentStep)) {
                        currentStep++;
                        showStep(currentStep);
                        updateStepIndicators();
                    }
                }
            });

            $form.on('click', '.englishline-form-prev', function(e) {
                e.preventDefault();
                
                const targetStep = currentStep - 1;
                if (timedOutSections.has(targetStep)) {
                    alert('No es posible regresar a una sección anterior cuyo tiempo ha finalizado.');
                    return false;
                }
                
                stopSectionTimer(currentStep);
                
                currentStep--;
                showStep(currentStep);
                updateStepIndicators();
            });

            $form.on('click', '.englishline-form-submit', function(e) {
                e.preventDefault();
                if (validateStep(currentStep)) {
                    submitForm();
                }
            });

            function showStep(stepIndex) {
                $steps.hide();
                $steps.eq(stepIndex).show();

                let $prevBtn = $form.find('.englishline-form-prev');
                let $nextBtn = $form.find('.englishline-form-next');
                let $submitBtn = $form.find('.englishline-form-submit');

                if (stepIndex === 0) {
                    $prevBtn.hide();
                } else {
                    $prevBtn.show();
                }

                if (stepIndex === totalSteps - 1) {
                    $nextBtn.hide();
                    $submitBtn.show();
                } else {
                    $submitBtn.hide();
                    $nextBtn.show();
                    $nextBtn.find('.button-text').text('Siguiente');
                    $nextBtn.find('.button-icon i').removeClass('dashicons-yes').addClass('dashicons-arrow-right-alt');
                }

                $('html, body').animate({
                    scrollTop: $form.offset().top - 50
                }, 300);

                initSectionTimer(stepIndex);
            }

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

            function validateStep(stepIndex) {
                let $currentStep = $steps.eq(stepIndex);
                let valid = true;

                $currentStep.find('[required]').each(function() {
                    if (!$(this).val()) {
                        valid = false;
                        $(this).addClass('error');

                        if (!$(this).next('.error-message').length) {
                            $(this).after('<span class="error-message">Este campo es obligatorio</span>');
                        }
                    } else {
                        $(this).removeClass('error');
                        $(this).next('.error-message').remove();
                    }
                });

                if (!valid) {
                    if (!$currentStep.find('.step-error-message').length) {
                        $currentStep.prepend('<div class="step-error-message">Por favor, completa todos los campos requeridos.</div>');

                        setTimeout(function() {
                            $currentStep.find('.step-error-message').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                }

                return valid;
            }

            function collectFormResponses() {
                let responses = {
                    sections: [],
                    user_info: {
                        first_name: $formElement.find('input[name="user_data[first_name]"]').val() || '',
                        last_name: $formElement.find('input[name="user_data[last_name]"]').val() || '',
                        email: $formElement.find('input[name="user_data[email]"]').val() || '',
                        phone: $formElement.find('input[name="user_data[phone]"]').val() || ''
                    },
                    form_title: $form.find('.englishline-form-title').text() || 'Test de Inglés',
                    submission_date: new Date().toLocaleString()
                };
                
                $steps.each(function(sectionIndex) {
                    // Ignorar la sección final (página de resultados)
                    if ($(this).data('step') === totalSteps) return; 
                    
                    let $section = $(this);
                    let sectionTitle = $section.find('.englishline-section-title').text() || ('Sección ' + (sectionIndex + 1));
                    
                    let sectionData = {
                        title: sectionTitle,
                        description: $section.find('.englishline-section-description').text() || '',
                        questions: []
                    };
                    
                    $section.find('.englishline-question').each(function() {
                        let $question = $(this);
                        let questionType = $question.data('question-type');
                        let questionId = $question.data('question-id') || '';
                        let questionText = $question.find('.form-question-prompt').text() || '';
                        
                        // Captura explícita de la propiedad isGradable
                        let isGradable = $question.data('is-gradable');
                        
                        // Si isGradable es undefined o null, verificamos el type
                        if (isGradable === undefined || isGradable === null) {
                            // Por defecto, las preguntas son calificables excepto title, paragraph, image
                            isGradable = !['title', 'paragraph', 'image'].includes(questionType);
                        } else {
                            // Convertir explícitamente a boolean por si viene como string "false"
                            isGradable = isGradable === true || isGradable === "true";
                        }
                        
                        let answer = getQuestionAnswer($question, questionType);
                        
                        let questionData = {
                            id: questionId,
                            type: questionType,
                            text: questionText,
                            answer: answer,
                            isGradable: isGradable
                        };
                        
                        sectionData.questions.push(questionData);
                    });
                    
                    responses.sections.push(sectionData);
                });
                
                return responses;
            }
            
            function getQuestionAnswer($question, type) {
                switch(type) {
                    case 'text':
                        return $question.find('input[type="text"]').val() || '';
                        
                    case 'textarea':
                        return $question.find('textarea').val() || '';
                        
                    case 'select':
                        let selectVal = $question.find('select').val();
                        let selectText = $question.find('select option:selected').text();
                        return {
                            value: selectVal,
                            text: selectText
                        };
                        
                    case 'radio':
                        let $checkedRadio = $question.find('input[type="radio"]:checked');
                        if (!$checkedRadio.length) return null;
                        
                        return {
                            value: $checkedRadio.val(),
                            text: $checkedRadio.next('label').text().trim()
                        };
                        
                    case 'checkbox':
                        let checkValues = [];
                        $question.find('input[type="checkbox"]:checked').each(function() {
                            checkValues.push({
                                value: $(this).val(),
                                text: $(this).next('label').text().trim()
                            });
                        });
                        return checkValues;
                        
                    case 'cloze':
                        let clozeValues = [];
                        $question.find('input.cloze-blank').each(function() {
                            clozeValues.push($(this).val() || '');
                        });
                        return clozeValues;
                        
                    case 'ordering':
                        let orderItems = [];
                        $question.find('.ordering-item').each(function(index) {
                            let itemValue = $(this).find('input[type="hidden"]').val();
                            orderItems.push({
                                value: itemValue,
                                text: $(this).find('.ordering-text').text().trim(),
                                position: index
                            });
                        });
                        return orderItems;
                        
                    case 'true-false':
                        let $checkedTF = $question.find('input[type="radio"]:checked');
                        if (!$checkedTF.length) return null;
                        
                        return $checkedTF.val() === 'true' ? 'Verdadero' : 'Falso';
                        
                    case 'image':
                        return $question.find('textarea').val() || '';
                        
                    default:
                        return '';
                }
            }
            
            function submitForm() {
                let $submitBtn = $form.find('.englishline-form-submit');
                let submitBtnText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Enviando...');
                $form.addClass('submitting');
                $form.find('.englishline-loader').show();
                $form.find('.englishline-form-error').hide();

                // 1. Recolectar las respuestas del formulario sin calificar
                let formResponses = collectFormResponses();
                console.log('Respuestas recopiladas:', formResponses);
                
                // 2. Preparar los datos para enviar al servidor
                let formData = new FormData($formElement[0]);
                
                formData.append('action', 'englishline_form_submit'); 
                formData.append('nonce', englishline_test.nonce);
                formData.append('form_id', $form.data('form-id'));
                formData.append('form_responses', JSON.stringify(formResponses));
                formData.append('form_title', $form.find('.englishline-form-title').text() || 'Test de Inglés');
                formData.append('status', 'pending'); // Marcar como pendiente para evaluación en servidor
                
                // 3. Enviar datos al servidor
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
                            $formElement.hide();
                            
                            // 4. Mostrar mensaje apropiado según la respuesta
                            let resultMessage = '<h3>¡Gracias por completar el test!</h3>' +
                                '<p>' + response.data.message + '</p>';
                                
                            // Si el servidor ha evaluado y devuelto una puntuación
                            if (response.data.score !== undefined) {
                                resultMessage += '<div class="test-results">' +
                                    '<p><strong>Tu puntuación:</strong> ' + response.data.score + '%</p>' +
                                    '<p><strong>Nivel CEFR:</strong> ' + response.data.level + '</p>' +
                                '</div>';
                            } else {
                                // Si estamos en "pending" y necesitamos mostrar un mensaje mientras se procesa
                                resultMessage += '<div class="test-results pending">' +
                                    '<p>Tu test está siendo procesado. Recibirás los resultados por correo electrónico.</p>' +
                                '</div>';
                            }
                            
                            $form.find('.englishline-form-success')
                                .html(resultMessage)
                                .show();

                            // 5. Redireccionar si es necesario
                            if (response.data && response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 2000);
                            }
                        } else {
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

                        $submitBtn.prop('disabled', false).text(submitBtnText);
                        $form.find('.englishline-form-error')
                            .text('Error de conexión. Por favor, intenta nuevamente más tarde.')
                            .show();
                    }
                });
            }
        });
    }

    initInputValidation();

    function initInputValidation() {
        $('.englishline-form-container').on('blur', 'input[required], select[required], textarea[required]', function() {
            let $field = $(this);

            if (!$field.val()) {
                $field.addClass('error');

                if (!$field.next('.error-message').length) {
                    $field.after('<span class="error-message">Este campo es obligatorio</span>');
                }
            } else {
                $field.removeClass('error');
                $field.next('.error-message').remove();
            }
        });

        $('.englishline-form-container').on('blur', 'input[type="email"]', function() {
            let $field = $(this);
            let email = $field.val();

            if (email && !validateEmail(email)) {
                $field.addClass('error');

                if (!$field.next('.error-message').length) {
                    $field.after('<span class="error-message">Por favor, ingresa un email válido</span>');
                } else {
                    $field.next('.error-message').text('Por favor, ingresa un email válido');
                }
            }
        });
    }

    function validateEmail(email) {
        let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

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

        if (currentChars > maxChars) {
            $textarea.val($textarea.val().substring(0, maxChars));
            $counter.text(maxChars + ' / ' + maxChars + ' caracteres');
        }

        if (currentChars > maxChars * 0.9) {
            $counter.addClass('limit-near');
        } else {
            $counter.removeClass('limit-near');
        }
    });

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
        
        .test-results {
            background-color: #f5f8ff;
            border: 1px solid #dde5f5;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .test-results.pending {
            background-color: #fffde7;
            border-color: #ffd54f;
        }
        
        .test-results p {
            margin: 5px 0;
        }
    `;
    
    $('head').append('<style>' + timerStyles + '</style>');
});
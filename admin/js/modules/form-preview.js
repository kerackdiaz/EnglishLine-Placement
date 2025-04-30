(function($) {
    'use strict';

    window.EnglishLineTest = window.EnglishLineTest || {};

    window.EnglishLineTest.FormPreview = {
        previewData: {
            sections: []
        },

        init: function() {
            this.setupEventListeners();
            this.addRefreshButton();
            this.updatePreview();
        },

        addRefreshButton: function() {
            let $formPreview = $('#form-preview');
            if ($formPreview.find('.preview-refresh-btn').length === 0) {
                $formPreview.find('.form-preview-header').append(
                    '<button type="button" class="preview-refresh-btn" style="margin-top: 10px; padding: 5px 10px; background-color: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer;"><span class="dashicons dashicons-update" style="margin-right: 5px;"></span>Actualizar vista previa</button>'
                );
            }
            $formPreview.on('click', '.preview-refresh-btn', this.updatePreview.bind(this));
        },

        setupEventListeners: function() {
            let self = this;
            

            $(document).on("englishline_form_data_changed", function() {
                $('.preview-refresh-btn').css('background-color', '#ffeb3b')
                    .text('Actualizar para ver cambios');
            });

            $(document).on('change', '.form-section-field, .form-question-field, .option-text', function() {
                $('.preview-refresh-btn').css('background-color', '#ffeb3b')
                    .text('Actualizar para ver cambios');
            });


            $(document).on('click', '.preview-prev-btn', function() {
                let $sections = $('.preview-section');
                let activeIndex = $('.preview-section.active').data('section-index') || 0;
                if (activeIndex > 0) {
                    $sections.removeClass('active');
                    $sections.filter('[data-section-index="' + (activeIndex - 1) + '"]').addClass('active');
                    $('.preview-dot').removeClass('active');
                    $('.preview-dot[data-index="' + (activeIndex - 1) + '"]').addClass('active');
                    $('.preview-next-btn').prop('disabled', false);
                    $('.preview-prev-btn').prop('disabled', activeIndex - 1 === 0);
                }
            });

            $(document).on('click', '.preview-next-btn', function() {
                let $sections = $('.preview-section');
                let activeIndex = $('.preview-section.active').data('section-index') || 0;
                let totalSections = $sections.length;
                if (activeIndex < totalSections - 1) {
                    $sections.removeClass('active');
                    $sections.filter('[data-section-index="' + (activeIndex + 1) + '"]').addClass('active');
                    $('.preview-dot').removeClass('active');
                    $('.preview-dot[data-index="' + (activeIndex + 1) + '"]').addClass('active');
                    $('.preview-prev-btn').prop('disabled', false);
                    $('.preview-next-btn').prop('disabled', activeIndex + 1 === totalSections - 1);
                }
            });

            $(document).on('click', '.preview-dot', function() {
                let index = $(this).data('index');
                let $sections = $('.preview-section');
                let totalSections = $sections.length;
                $sections.removeClass('active');
                $sections.filter('[data-section-index="' + index + '"]').addClass('active');
                $('.preview-dot').removeClass('active');
                $(this).addClass('active');
                $('.preview-prev-btn').prop('disabled', index === 0);
                $('.preview-next-btn').prop('disabled', index === totalSections - 1);
            });

            $(document).on('mouseenter', '.preview-ordering-list', function() {
                let $list = $(this);
                if (!$list.hasClass('ui-sortable')) {
                    $list.sortable({
                        placeholder: 'preview-ordering-item-placeholder',
                    });
                }
            });
        },

        renderTextQuestion: function(questionData) {
            return `<input type="text" class="preview-text-input widefat" placeholder="Respuesta corta" ${questionData.maxChars ? `maxlength="${questionData.maxChars}"` : ''}>`;
        },

        renderTextareaQuestion: function(questionData) {
            return `<textarea class="preview-textarea-input widefat" rows="4" placeholder="Respuesta larga" ${questionData.maxChars ? `maxlength="${questionData.maxChars}"` : ''}></textarea>`;
        },

        renderSelectQuestion: function(questionData) {
            
            if (!questionData.options || !Array.isArray(questionData.options)) {
                return '<select class="preview-select-input widefat"><option value="">- Error: No hay opciones -</option></select>';
            }
            
            let optionsHTML = questionData.options.map(option => 
                `<option value="${option.text}">${option.text}</option>`
            ).join('');
            
            return `<select class="preview-select-input widefat">
                <option value="">- Seleccionar -</option>
                ${optionsHTML}
            </select>`;
        },

        renderRadioQuestion: function(questionData, questionIndex, sectionIndex) {
            
            if (!questionData.options || !Array.isArray(questionData.options)) {
                return '<div class="preview-radio-group">Error: No hay opciones</div>';
            }
            
            let radioOptionsHTML = questionData.options.map((option, index) => `
                <div class="preview-radio-option">
                    <input type="radio" name="preview_question_${sectionIndex}_${questionIndex}" id="radio_option_${sectionIndex}_${questionIndex}_${index}">
                    <label for="radio_option_${sectionIndex}_${questionIndex}_${index}">${option.text}</label>
                </div>
            `).join('');
            
            return `<div class="preview-radio-group">${radioOptionsHTML}</div>`;
        },

        renderCheckboxQuestion: function(questionData, questionIndex, sectionIndex) {
            
            if (!questionData.options || !Array.isArray(questionData.options)) {
                return '<div class="preview-checkbox-group">Error: No hay opciones</div>';
            }
            
            let checkboxOptionsHTML = questionData.options.map((option, index) => `
                <div class="preview-checkbox-option">
                    <input type="checkbox" id="checkbox_option_${sectionIndex}_${questionIndex}_${index}">
                    <label for="checkbox_option_${sectionIndex}_${questionIndex}_${index}">${option.text}</label>
                </div>
            `).join('');
            
            return `<div class="preview-checkbox-group">${checkboxOptionsHTML}</div>`;
        },

        renderImageQuestion: function(questionData) {
            
            let imageHtml = '';
            if (questionData.imageUrl) {
                imageHtml = `<div class="image-preview-container"><img src="${questionData.imageUrl}" alt="Vista previa"></div>`;
            } else if (questionData.imageId) {
                imageHtml = `<div class="image-placeholder">Imagen con ID ${questionData.imageId} (URL no disponible)</div>`;
            } else {
                imageHtml = '<div class="image-placeholder">Imagen no seleccionada</div>';
            }
            
            return `
                <div class="preview-image-question">
                    ${imageHtml}
                    <textarea class="preview-textarea-input widefat" rows="3" 
                        placeholder="${questionData.imageQuestionText || 'Escribe tu descripción aquí'}"
                        ${questionData.imageMaxChars ? `maxlength="${questionData.imageMaxChars}"` : ''}>
                    </textarea>
                </div>
            `;
        },

        renderTitleQuestion: function(questionData) {
            let titleTag = questionData.titleSize || 'h2';
            let alignment = questionData.titleAlignment || 'center';
            
            return `<div class="preview-title" style="text-align: ${alignment}">
                <${titleTag}>${questionData.text}</${titleTag}>
            </div>`;
        },

        renderParagraphQuestion: function(questionData) {           
            let alignment = questionData.paragraphAlignment || 'left';
            let content = questionData.paragraphContent || questionData.content || '';

            return `
                <div class="preview-paragraph-header">
                    <h4 class="preview-paragraph-title">${questionData.text}</h4>
                </div>
                <div class="preview-paragraph" style="text-align: ${alignment}">
                    <p>${content}</p>
                </div>
            `;
        },

        renderTrueFalseQuestion: function(questionData) {
            return `
                <div class="preview-question-text">${questionData.statement || 'Afirmación Verdadero/Falso'}</div>
                <div class="preview-true-false-options ${questionData.immediateFeedback ? 'feedback-enabled' : ''}">
                    <span class="preview-true-false-option" data-value="true">Verdadero</span> / 
                    <span class="preview-true-false-option" data-value="false">Falso</span>
                </div>
            `;
        },

        renderClozeQuestion: function(questionData) {
            
            let clozeText = questionData.clozeText || 'Texto con [espacios] para completar';
            let blanksFound = 0;
            let clozePreview = clozeText.replace(/\[(.*?)\]/g, function(match, word) {
                let blankIndex = blanksFound;
                blanksFound++;
                return `<input type="text" class="preview-cloze-input" data-blank-index="${blankIndex}" placeholder="${word}" />`;
            });
            
            
            return `
                <div class="cloze-preview">${clozePreview}</div>
            `;
        },

        renderOrderingQuestion: function(questionData) {

            let itemsText = '';
            
            if (questionData.itemsText) {
                itemsText = questionData.itemsText;
            } else if (questionData.items_text) {
                itemsText = questionData.items_text;
            } else if (questionData.items && Array.isArray(questionData.items)) {
                itemsText = questionData.items.join('\n');
            } else if (questionData.orderItems && Array.isArray(questionData.orderItems)) {
                itemsText = questionData.orderItems.join('\n');
            }
            
            if (!itemsText) {
                return '<div class="preview-error">Error: No hay elementos para ordenar</div>';
            }
            
            let orderingItems = itemsText.split('\n').filter(item => item.trim() !== '');
            
            let orderingPreview = '<ol class="preview-ordering-list">';
            
            orderingItems.forEach((item, index) => {
                orderingPreview += `<li class="preview-ordering-item" data-item-index="${index}">${item.trim()}</li>`;
            });
            orderingPreview += '</ol>';
            
            return orderingPreview;
        },

        updatePreview: function() {
            let self = this;
            
            if ($('.preview-refresh-btn').length > 0) {
                $('.preview-refresh-btn').css('background-color', '#f0f0f1')
                    .html('<span class="dashicons dashicons-update" style="margin-right: 5px;"></span>Actualizar vista previa');
            }
            
            let $previewSections = $('.preview-sections');
            let $formPreview = $('#form-preview');
            

            if (EnglishLineTest.FormData && EnglishLineTest.FormData.sectionsData) {
                this.previewData.sections = JSON.parse(JSON.stringify(EnglishLineTest.FormData.sectionsData));
            } else {
                this.collectDataFromDOM();
            }
            
            let totalSections = this.previewData.sections.length;
            
            let currentActiveIndex = Math.min($('.preview-section.active').data('section-index') || 0, totalSections - 1);
            
            if (currentActiveIndex < 0) currentActiveIndex = 0;

            if (totalSections === 0) {
                $previewSections.html(
                    '<div class="preview-empty">' +
                    'El examen aún no tiene secciones. Añade secciones y preguntas para ver la vista previa.' +
                    '</div>'
                );
                if ($formPreview.find('.preview-nav').length > 0) {
                    $formPreview.find('.preview-nav').hide();
                }
                return;
            }
            
            $previewSections.empty();
            
            this.previewData.sections.forEach((sectionData, sectionIndex) => {
                
                let $previewSection = $(`
                    <div class="preview-section" data-section-index="${sectionIndex}">
                        <h3 class="preview-section-title">${sectionData.title || 'Sección ' + (sectionIndex + 1)}</h3>
                    </div>
                `);
                
                if (sectionIndex === currentActiveIndex) {
                    $previewSection.addClass('active');
                }
                
                if (sectionData.description) {
                    $previewSection.append(`
                        <div class="preview-section-description">${sectionData.description}</div>
                    `);
                }

                let timeLimit = parseInt(sectionData.timeLimit) || parseInt(sectionData.time_limit) || 0;
                if (timeLimit > 0) {
                    $previewSection.append(`
                        <div class="preview-section-time">
                            <span class="dashicons dashicons-clock"></span> 
                            Límite de tiempo: ${timeLimit} minutos
                        </div>
                    `);
                }
                

                let $previewQuestionsContainer = $('<div class="preview-questions"></div>');

                if (sectionData.questions && sectionData.questions.length) {
                    
                    sectionData.questions.forEach((questionData, questionIndex) => {
                        
                        self.normalizeQuestionData(questionData);
                        
                        let $previewQuestion = $(`
                            <div class="preview-question" data-question-index="${questionIndex}" data-question-type="${questionData.type}">
                                <div class="preview-question-header">
                                    ${(questionData.type !== 'title' && questionData.type !== 'paragraph') ? 
                                        `<div class="preview-question-number">${questionIndex + 1}. </div>` : ''}
                                    ${(questionData.type !== 'title' && questionData.type !== 'paragraph') ? 
                                        `<div class="preview-question-text-container">
                                            <div class="preview-question-text">${questionData.text}${questionData.required ? ' <span class="required">*</span>' : ''}</div>
                                        </div>` : ''}
                                </div>
                                <div class="preview-question-content"></div>
                            </div>
                        `);
                        

                        let questionContentHTML = '';
                        
                        switch (questionData.type) {
                            case 'title':
                                questionContentHTML = self.renderTitleQuestion(questionData);
                                break;
                            case 'paragraph':
                                questionContentHTML = self.renderParagraphQuestion(questionData);
                                break;
                            case 'true-false':
                                questionContentHTML = self.renderTrueFalseQuestion(questionData);
                                break;
                            case 'cloze':
                                questionContentHTML = self.renderClozeQuestion(questionData);
                                break;
                            case 'ordering':
                                questionContentHTML = self.renderOrderingQuestion(questionData);
                                break;
                            case 'text':
                                questionContentHTML = self.renderTextQuestion(questionData);
                                break;
                            case 'textarea':
                                questionContentHTML = self.renderTextareaQuestion(questionData);
                                break;
                            case 'select':
                                questionContentHTML = self.renderSelectQuestion(questionData);
                                break;
                            case 'radio':
                                questionContentHTML = self.renderRadioQuestion(questionData, questionIndex, sectionIndex);
                                break;
                            case 'checkbox':
                                questionContentHTML = self.renderCheckboxQuestion(questionData, questionIndex, sectionIndex);
                                break;
                            case 'image':
                                questionContentHTML = self.renderImageQuestion(questionData);
                                break;
                            default:
                                questionContentHTML = '<div class="preview-error">Tipo de pregunta desconocido</div>';
                        }
                        
                        $previewQuestion.find('.preview-question-content').html(questionContentHTML);

                        if (questionData.hint) {
                            $previewQuestion.find('.preview-question-content').after(`
                                <div class="preview-question-hint">${questionData.hint}</div>
                            `);
                        }
                        
                        $previewQuestionsContainer.append($previewQuestion);
                    });
                }
                
                $previewSection.append($previewQuestionsContainer);
                $previewSections.append($previewSection);
            });
            

            if (totalSections > 1) {
                if ($formPreview.find('.preview-nav').length === 0) {
                    $previewSections.after(`
                        <div class="preview-nav" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                            <button class="preview-prev-btn" style="padding: 8px 15px; background-color: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; min-width: 100px;">Anterior</button>
                            <div class="preview-dots" style="display: flex; gap: 8px; justify-content: center; flex: 1; margin: 0 15px;"></div>
                            <button class="preview-next-btn" style="padding: 8px 15px; background-color: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; min-width: 100px;">Siguiente</button>
                        </div>
                    `);
                }
                

                let $dots = $formPreview.find('.preview-dots').empty();
                
                for (let i = 0; i < totalSections; i++) {
                    $dots.append(`
                        <span class="preview-dot${i === currentActiveIndex ? ' active' : ''}" 
                            data-index="${i}" 
                            style="width: 12px; height: 12px; border-radius: 50%; background-color: ${i === currentActiveIndex ? '#2271b1' : '#ccc'}; cursor: pointer; transition: background-color 0.2s;">
                        </span>
                    `);
                }
                
                $formPreview.find('.preview-nav').show();
                $formPreview.find('.preview-prev-btn').prop('disabled', currentActiveIndex === 0);
                $formPreview.find('.preview-next-btn').prop('disabled', currentActiveIndex === totalSections - 1);
            } else {

                if ($formPreview.find('.preview-nav').length > 0) {
                    $formPreview.find('.preview-nav').hide();
                }
            }

        },
        

        normalizeQuestionData: function(questionData) {
            if (!questionData) return;
            
            
            const propMap = {
                'cloze_text': 'clozeText',
                'case_sensitive': 'caseSensitive',
                'title_size': 'titleSize',
                'title_alignment': 'titleAlignment',
                'paragraph_alignment': 'paragraphAlignment',
                'paragraph_content': 'paragraphContent',
                'max_chars': 'maxChars',
                'feedback_correct': 'feedbackCorrect',
                'feedback_incorrect': 'feedbackIncorrect',
                'image_id': 'imageId',
                'image_url': 'imageUrl',
                'image_question_text': 'imageQuestionText',
                'image_max_chars': 'imageMaxChars',
                'items_text': 'itemsText',
                'correct_answer': 'correctAnswer',
                'correct_options': 'correctOptions',
                'immediate_feedback': 'immediateFeedback',
                'correct_value': 'correctAnswer',
                'correct_fills': 'correctFills'  
            };

            Object.keys(propMap).forEach(snakeCase => {
                if (questionData[snakeCase] !== undefined) {
                    questionData[propMap[snakeCase]] = questionData[snakeCase];
                }
            });
            

            if (questionData.type === 'true-false') {
                if (questionData.correctValue !== undefined && questionData.correctAnswer === undefined) {
                    questionData.correctAnswer = questionData.correctValue;
                }
            }

            if (questionData.type === 'cloze' && questionData.clozeText) {
                let matches = [];
                let regex = /\[(.*?)\]/g;
                let match;
                
                while ((match = regex.exec(questionData.clozeText)) !== null) {
                    let word = match[1].trim();
                    let accepted = [word];

                    if (questionData.correctFills && Array.isArray(questionData.correctFills)) {
                        let fillIndex = matches.length;
                        if (fillIndex < questionData.correctFills.length) {
                            accepted = [questionData.correctFills[fillIndex]];
                        }
                    }
                    
                    matches.push({
                        word: word,
                        accepted: accepted
                    });
                }
                
                questionData.answers = matches;
            }
        },
        
        collectDataFromDOM: function() {
            
            let self = this;
            this.previewData.sections = [];
            
            $('#form-sections-container .form-section').each(function(sectionIndex) {
                let $section = $(this);
                let sectionTitle = $section.find('.form-section-field[data-field="title"]').val() ||
                       $section.find('.form-section-title').text() ||
                       'Sección ' + (sectionIndex + 1);
                
                let sectionData = {
                    title: sectionTitle,
                    description: $section.find('.form-section-field[data-field="description"]').val() || '',
                    timeLimit: $section.find('.form-section-field[data-field="time_limit"]').val() || 0,
                    questions: []
                };
                
                self.previewData.sections[sectionIndex] = sectionData;
                
                $section.find('.form-question').each(function(questionIndex) {
                    let $question = $(this);
                    let questionType = $question.attr('data-question-type') || 'text';
                    let questionText = $question.find('.form-question-field[data-field="text"]').val() || 
                              $question.find('.form-question-text').text() || 
                              'Pregunta ' + (questionIndex + 1);
    
                    let questionData = {
                        type: questionType,
                        text: questionText,
                        required: $question.find('.form-question-field[data-field="required"]').prop('checked'),
                        hint: $question.find('.form-question-field[data-field="hint"]').val() || '',
                        options: []
                    };

                    switch(questionType) {
                        case 'title':
                            questionData.titleSize = $question.find('.form-question-field[data-field="title_size"]').val() || 'h2';
                            questionData.titleAlignment = $question.find('.form-question-field[data-field="title_alignment"]').val() || 'center';
                            break;
                            
                        case 'paragraph':
                            questionData.paragraphAlignment = $question.find('.form-question-field[data-field="paragraphAlignment"]').val() || 'left';
                            questionData.paragraphContent = $question.find('.form-question-field[data-field="paragraphContent"]').val() || '';
                            break;
                            
                        case 'text':
                        case 'textarea':
                            questionData.maxChars = $question.find('.form-question-field[data-field="max_chars"]').val() || 0;
                            break;
                            
                        case 'cloze':
                            questionData.clozeText = $question.find('.form-question-field[data-field="cloze_text"]').val() || '';
                            questionData.caseSensitive = $question.find('.form-question-field[data-field="case_sensitive"]').prop('checked');
                            break;
                            
                        case 'true-false':
                            questionData.statement = $question.find('.form-question-field[data-field="statement"]').val() || '';
                            questionData.correctAnswer = $question.find('.form-question-field[data-field="correct_answer"]').val() === 'true';
                            break;
                            
                            case 'ordering':
                                let orderingItems = $question.find('.form-question-field[data-field="itemsText"]').val();
                                
                                if (!orderingItems) {
                                    orderingItems = $question.find('.form-question-field[data-field="items_text"]').val();
                                }
                                
                                questionData.itemsText = orderingItems || '';
                                break;
                            
                        case 'image':
                            questionData.imageId = $question.find('.form-question-field[data-field="image_id"]').val() || '';
                            questionData.imageUrl = $question.find('.image-selector .image-preview img').attr('src') || '';
                            break;
                    }
                    
                    // Recopilar opciones para preguntas de selección
                    if (['select', 'radio', 'checkbox'].includes(questionType)) {
                        $question.find('.form-option').each(function(optionIndex) {
                            let optionText = $(this).find('.option-text').val() || '';
                            let optionCorrect = $(this).find('.option-correct').prop('checked');
                            questionData.options.push({
                                text: optionText,
                                correct: optionCorrect
                            });
                        });
                    }
                    
                    sectionData.questions.push(questionData);
                });
            });
        }
    };


    $(function() {
        setTimeout(function() {
            window.EnglishLineTest.FormPreview.init();
            
            $(document).on("englishline_form_data_loaded englishline_form_data_changed", function() {
                $('.preview-refresh-btn').css('background-color', '#ffeb3b').text('Actualizar para ver cambios');
            });
        }, 500);
    });

})(jQuery);
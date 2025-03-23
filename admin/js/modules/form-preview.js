(function($) {
    'use strict';

    window.EnglishLineTest = window.EnglishLineTest || {};

    window.EnglishLineTest.FormPreview = {
        previewData: {
            sections: []
        },

        init: function() {
            this.setupEventHandlers();
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

        setupEventHandlers: function() {
            let self = this;

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

            $(document).on('change', '.form-section-field, .form-question-field, .option-text, .drag-item-text, .drop-zone-name, .matching-left-item, .matching-right-item', function() {
                $('.preview-refresh-btn').css('background-color', '#ffeb3b').text('Actualizar para ver cambios');
            });

            $(document).on('click', '.preview-true-false-option', function() {
                let $option = $(this);
                let $question = $option.closest('.preview-question');
                let sectionIndex = $question.closest('.preview-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let questionData = self.previewData.sections[sectionIndex].questions[questionIndex];
                let selectedValue = $option.data('value');

                $question.find('.preview-true-false-option').removeClass('selected');
                $option.addClass('selected');

                if (questionData.immediate_feedback) {
                    let isCorrect = (selectedValue === questionData.correct_answer.toString());
                    $question.find('.preview-true-false-options').removeClass('correct incorrect');
                    $question.find('.preview-true-false-feedback').remove();

                    if (isCorrect) {
                        $question.find('.preview-true-false-options').addClass('correct');
                        let feedbackText = questionData.feedback_correct || '¡Correcto!';
                        $question.find('.preview-question-content').append(
                            '<div class="preview-true-false-feedback correct">' + feedbackText + '</div>'
                        );
                    } else {
                        $question.find('.preview-true-false-options').addClass('incorrect');
                        let feedbackText = questionData.feedback_incorrect || 'Incorrecto';
                        $question.find('.preview-question-content').append(
                            '<div class="preview-true-false-feedback incorrect">' + feedbackText + '</div>'
                        );
                    }
                }
            });

            $(document).on('input', '.preview-cloze-input', function() {
                let $input = $(this);
                let $question = $input.closest('.preview-question');
                let sectionIndex = $question.closest('.preview-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let questionData = self.previewData.sections[sectionIndex].questions[questionIndex];
                let blankIndex = parseInt($input.data('blank-index'), 10);
                let currentValue = $input.val();

                if (questionData.immediate_feedback && questionData.answers && questionData.answers[blankIndex]) {
                    let acceptedAnswers = questionData.answers[blankIndex].accepted || [];
                    let isCorrect = false;

                    for (let i = 0; i < acceptedAnswers.length; i++) {
                        let answer = acceptedAnswers[i];
                        if (questionData.case_sensitive) {
                            if (currentValue === answer) {
                                isCorrect = true;
                                break;
                            }
                        } else {
                            if (currentValue.toLowerCase() === answer.toLowerCase()) {
                                isCorrect = true;
                                break;
                            }
                        }
                    }

                    $input.removeClass('correct incorrect');
                    if (currentValue) {
                        $input.addClass(isCorrect ? 'correct' : 'incorrect');
                    }
                }
            });

            $(document).on('mouseenter', '.preview-ordering-list', function() {
                let $list = $(this);
                if (!$list.hasClass('ui-sortable')) {
                    $list.sortable({
                        placeholder: 'preview-ordering-item-placeholder',
                        update: function(event, ui) {
                            let $question = $list.closest('.preview-question');
                            let sectionIndex = $question.closest('.preview-section').data('section-index');
                            let questionIndex = $question.data('question-index');
                            let questionData = self.previewData.sections[sectionIndex].questions[questionIndex];

                            if (questionData.immediate_feedback) {
                                let currentOrder = [];
                                $list.find('.preview-ordering-item').each(function() {
                                    currentOrder.push(parseInt($(this).data('item-index'), 10));
                                });

                                let isCorrect = true;
                                for (let i = 0; i < currentOrder.length; i++) {
                                    if (currentOrder[i] !== i) {
                                        isCorrect = false;
                                        break;
                                    }
                                }

                                $question.find('.preview-ordering-feedback').remove();
                                if (isCorrect) {
                                    $question.find('.preview-question-content').append(
                                        '<div class="preview-ordering-feedback correct">¡Orden correcto!</div>'
                                    );
                                } else {
                                    $question.find('.preview-question-content').append(
                                        '<div class="preview-ordering-feedback incorrect">El orden no es correcto</div>'
                                    );
                                }
                            }
                        }
                    });
                }
            });

            $(document).on('mouseenter', '.preview-drag-drop-container', function() {
                let $container = $(this);
                let $question = $container.closest('.preview-question');
                let sectionIndex = $question.closest('.preview-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let questionData = self.previewData.sections[sectionIndex].questions[questionIndex];

                if (!$container.hasClass('drag-drop-initialized')) {
                    $container.find('.preview-drag-item').draggable({
                        helper: 'clone',
                        revert: 'invalid',
                        zIndex: 100,
                        start: function(event, ui) {
                            $(ui.helper).addClass('being-dragged');
                        }
                    });

                    $container.find('.preview-drop-zone').droppable({
                        accept: '.preview-drag-item',
                        hoverClass: 'drop-hover',
                        drop: function(event, ui) {
                            let $zone = $(this);
                            let zoneIndex = parseInt($zone.data('zone-index'), 10);
                            let $dragItem = $(ui.draggable);
                            let itemIndex = parseInt($dragItem.data('item-index'), 10);
                            let multiple = questionData.multiple_per_zone;

                            let $existingItem = $zone.find('.dropped-item[data-item-index="' + itemIndex + '"]');
                            if ($existingItem.length > 0) {
                                return;
                            }

                            if (!multiple && $zone.find('.dropped-item').length > 0) {
                                $zone.find('.dropped-item').remove();
                            }

                            let $clone = $('<div class="dropped-item"></div>')
                                .text($dragItem.text())
                                .data('item-index', itemIndex)
                                .append('<span class="remove-dropped-item">×</span>');

                            $zone.append($clone);

                            if (questionData.immediate_feedback) {
                                let dropZoneData = questionData.drop_zones[zoneIndex];
                                let isValid = dropZoneData.validItems && dropZoneData.validItems.includes(itemIndex);
                                $clone.addClass(isValid ? 'correct' : 'incorrect');
                            }
                        }
                    });

                    $container.on('click', '.remove-dropped-item', function() {
                        $(this).parent('.dropped-item').remove();
                    });

                    $container.addClass('drag-drop-initialized');
                }
            });

            $(document).on('mouseenter', '.preview-matching-container', function() {
                let $container = $(this);
                let matchingMethod = $container.data('method') || 'lines';

                if (matchingMethod === 'lines' && !$container.hasClass('lines-initialized')) {
                    let $svg = $container.find('.preview-matching-lines');
                    let $leftItems = $container.find('.preview-matching-left-item');
                    let $rightItems = $container.find('.preview-matching-right-item');
                    let connections = [];
                    let currentLine = null;

                    $container.on('mousedown', '.preview-matching-left-item', function(e) {
                        e.preventDefault();
                        let $item = $(this);
                        let leftIndex = parseInt($item.data('index'), 10);
                        let leftPos = $item.offset();
                        let containerPos = $container.offset();

                        currentLine = {
                            leftIndex: leftIndex,
                            rightIndex: null,
                            x1: leftPos.left + $item.outerWidth() - containerPos.left,
                            y1: leftPos.top + $item.outerHeight()/2 - containerPos.top
                        };

                        $svg.append('<line class="temp-line" x1="' + currentLine.x1 + '" y1="' + currentLine.y1 +
                                    '" x2="' + (e.pageX - containerPos.left) + '" y2="' + (e.pageY - containerPos.top) + '" />');
                    });

                    $container.on('mousemove', function(e) {
                        if (currentLine) {
                            let containerPos = $container.offset();
                            $svg.find('.temp-line').attr({
                                'x2': e.pageX - containerPos.left,
                                'y2': e.pageY - containerPos.top
                            });
                        }
                    });

                    $container.on('mouseup', '.preview-matching-right-item', function(e) {
                        if (currentLine) {
                            let $item = $(this);
                            let rightIndex = parseInt($item.data('index'), 10);
                            let rightPos = $item.offset();
                            let containerPos = $container.offset();

                            currentLine.rightIndex = rightIndex;
                            currentLine.x2 = rightPos.left - containerPos.left;
                            currentLine.y2 = rightPos.top + $item.outerHeight()/2 - containerPos.top;

                            connections = connections.filter(function(conn) {
                                if (conn.leftIndex === currentLine.leftIndex) {
                                    $svg.find('line[data-left="' + conn.leftIndex + '"]').remove();
                                    return false;
                                }
                                return true;
                            });

                            connections.push(currentLine);

                            $svg.find('.temp-line').remove();
                            $svg.append('<line data-left="' + currentLine.leftIndex + '" data-right="' + currentLine.rightIndex +
                                        '" x1="' + currentLine.x1 + '" y1="' + currentLine.y1 +
                                        '" x2="' + currentLine.x2 + '" y2="' + currentLine.y2 + '" />');

                            let $question = $container.closest('.preview-question');
                            let sectionIndex = $question.closest('.preview-section').data('section-index');
                            let questionIndex = $question.data('question-index');
                            let questionData = self.previewData.sections[sectionIndex].questions[questionIndex];

                            if (questionData.immediate_feedback) {
                                let isCorrect = currentLine.leftIndex === currentLine.rightIndex;
                                $svg.find('line[data-left="' + currentLine.leftIndex + '"]')
                                    .addClass(isCorrect ? 'correct' : 'incorrect');
                            }

                            currentLine = null;
                        }
                    });

                    $(document).on('mouseup', function() {
                        if (currentLine) {
                            $svg.find('.temp-line').remove();
                            currentLine = null;
                        }
                    });

                    $container.addClass('lines-initialized');
                }

                if (matchingMethod === 'dropdown' && !$container.hasClass('dropdown-initialized')) {
                    $container.on('change', '.preview-matching-dropdown', function() {
                        let $select = $(this);
                        let $item = $select.closest('.preview-matching-left-item');
                        let leftIndex = parseInt($item.data('index'), 10);
                        let rightIndex = parseInt($select.val(), 10);

                        let $question = $container.closest('.preview-question');
                        let sectionIndex = $question.closest('.preview-section').data('section-index');
                        let questionIndex = $question.data('question-index');
                        let questionData = self.previewData.sections[sectionIndex].questions[questionIndex];

                        if (questionData.immediate_feedback) {
                            let isCorrect = leftIndex === rightIndex;
                            $select.removeClass('correct incorrect');
                            if (rightIndex !== -1) {
                                $select.addClass(isCorrect ? 'correct' : 'incorrect');
                            }
                        }
                    });

                    $container.addClass('dropdown-initialized');
                }
            });
        },

        renderTextQuestion: function(questionData, questionIndex, sectionIndex) {
            return '<input type="text" class="preview-text-input widefat" placeholder="Respuesta corta">';
        },

        renderTextareaQuestion: function(questionData, questionIndex, sectionIndex) {
            return '<textarea class="preview-textarea-input widefat" rows="4" placeholder="Respuesta larga"></textarea>';
        },

        renderSelectQuestion: function(questionData, questionIndex, sectionIndex) {
            let optionsHTML = questionData.options.map(option => `<option value="${option.text}">${option.text}</option>`).join('');
            return `<select class="preview-select-input widefat"><option value="">- Seleccionar -</option>${optionsHTML}</select>`;
        },

        renderRadioQuestion: function(questionData, questionIndex, sectionIndex) {
            let radioOptionsHTML = questionData.options.map((option, index) => `
                <div class="preview-radio-option">
                    <input type="radio" name="preview_question_${sectionIndex}_${questionIndex}" id="radio_option_${sectionIndex}_${questionIndex}_${index}">
                    <label for="radio_option_${sectionIndex}_${questionIndex}_${index}">${option.text}</label>
                </div>
            `).join('');
            return `<div class="preview-radio-group">${radioOptionsHTML}</div>`;
        },

        renderCheckboxQuestion: function(questionData, questionIndex, sectionIndex) {
            let checkboxOptionsHTML = questionData.options.map((option, index) => `
                <div class="preview-checkbox-option">
                    <input type="checkbox" id="checkbox_option_${sectionIndex}_${questionIndex}_${index}">
                    <label for="checkbox_option_${sectionIndex}_${questionIndex}_${index}">${option.text}</label>
                </div>
            `).join('');
            return `<div class="preview-checkbox-group">${checkboxOptionsHTML}</div>`;
        },

        renderImageQuestion: function(questionData, questionIndex, sectionIndex) {
            let imageHtml = '';
            if (questionData.image_url) {
                imageHtml = `<div class="image-preview-container"><img src="${questionData.image_url}" alt="Vista previa"></div>`;
            } else {
                imageHtml = '<div class="image-placeholder">Imagen no seleccionada</div>';
            }
            return `
                <div class="form-question-text">${questionData.text || 'Describa la imagen'}</div>
                ${imageHtml}
                <textarea class="preview-textarea-input widefat" rows="3" placeholder="${questionData.image_question_text || 'Escribe tu descripción aquí'}"></textarea>
            `;
        },

        renderTrueFalseQuestion: function(questionData, questionIndex, sectionIndex) {
            return `
                <div class="preview-question-text">${questionData.statement || 'Afirmación Verdadero/Falso'}</div>
                <div class="preview-true-false-options ${questionData.immediate_feedback ? 'feedback-enabled' : ''}">
                    <span class="preview-true-false-option" data-value="true">Verdadero</span> / <span class="preview-true-false-option" data-value="false">Falso</span>
                </div>
            `;
        },

        renderClozeQuestion: function(questionData, questionIndex, sectionIndex) {
            let clozeText = questionData.cloze_text || 'Texto con [espacios] para completar';
            let clozePreview = clozeText.replace(/\[(.*?)\]/g, (match, word, index) => {
                return `<input type="text" class="preview-cloze-input" data-blank-index="${index}" placeholder="${word}" />`;
            });
            return `
                <div class="form-question-text">${questionData.text || 'Completa el texto'}</div>
                <div class="cloze-preview">${clozePreview}</div>
            `;
        },

        renderOrderingQuestion: function(questionData, questionIndex, sectionIndex) {
            let orderingItemsText = questionData.items_text || "Elemento 1\nElemento 2\nElemento 3";
            let orderingItems = orderingItemsText.split('\n').filter(item => item.trim() !== '');
            let orderingPreview = '<ol class="preview-ordering-list">';
            orderingItems.forEach((item, index) => {
                orderingPreview += `<li class="preview-ordering-item" data-item-index="${index}">${item.trim()}</li>`;
            });
            orderingPreview += '</ol>';
            return `
                <div class="form-question-text">${questionData.text || 'Ordena los elementos'}</div>
                ${orderingPreview}
            `;
        },

        renderDragDropQuestion: function(questionData, questionIndex, sectionIndex) {
            let dragDropItems = questionData.drag_items || [{text: 'Elemento 1'}, {text: 'Elemento 2'}];
            let dragDropZones = questionData.drop_zones || [{name: 'Zona 1'}, {name: 'Zona 2'}];
            let dragDropPreview = '<div class="preview-drag-drop-container">';
            dragDropPreview += '<div class="preview-drag-items">';
            dragDropItems.forEach((item, index) => {
                dragDropPreview += `<span class="preview-drag-item" data-item-index="${index}">${item.text.trim()}</span>`;
            });
            dragDropPreview += '</div>';
            dragDropPreview += '<div class="preview-drop-zones">';
            dragDropZones.forEach((zone, index) => {
                dragDropPreview += `<div class="preview-drop-zone" data-zone-index="${index}">${zone.name || 'Zona'}</div>`;
            });
            dragDropPreview += '</div>';
            dragDropPreview += '</div>';
            return `
                <div class="form-question-text">${questionData.text || 'Arrastrar y soltar'}</div>
                ${dragDropPreview}
            `;
        },

        renderMatchingQuestion: function(questionData, questionIndex, sectionIndex) {
            let matchingPairs = questionData.pairs || [{left: 'Izquierda 1', right: 'Derecha 1'}, {left: 'Izquierda 2', right: 'Derecha 2'}];
            let matchingPreview = '<div class="preview-matching-container" data-method="' + (questionData.matching_method || 'lines') + '">';
            if (questionData.matching_method === 'lines') {
                matchingPreview += '<svg class="preview-matching-lines"></svg>';
            }
            matchingPreview += '<ul class="preview-matching-left-items">';
            matchingPairs.forEach((pair, index) => {
                matchingPreview += `<li class="preview-matching-left-item" data-index="${index}">${pair.left.trim()}
                    ${questionData.matching_method === 'dropdown' ? `<select class="preview-matching-dropdown">
                        <option value="-1">- Selecciona -</option>` + matchingPairs.map((p, i) => `<option value="${i}">${p.right.trim()}</option>`).join('') + `</select>` : ''}
                </li>`;
            });
            matchingPreview += '</ul><ul class="preview-matching-right-items">';
            matchingPairs.forEach((pair, index) => {
                matchingPreview += `<li class="preview-matching-right-item" data-index="${index}">${pair.right.trim()}</li>`;
            });
            matchingPreview += '</ul></div>';
            return `
                <div class="form-question-text">${questionData.text || 'Empareja las columnas'}</div>
                ${matchingPreview}
            `;
        },


        updatePreview: function() {
            let self = this;

            if ($('.preview-refresh-btn').length > 0) {
                $('.preview-refresh-btn').css('background-color', '#f0f0f1')
                    .html('<span class="dashicons dashicons-update" style="margin-right: 5px;"></span>Actualizar vista previa');
            }

            let $previewSections = $('.preview-sections');
            let $formPreview = $('#form-preview');
            let currentActiveIndex = $('.preview-section.active').data('section-index') || 0;
            let $formSections = $('#form-sections-container .form-section');
            let totalSections = $formSections.length;


            self.previewData.sections = [];

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

            if (currentActiveIndex >= totalSections) {
                currentActiveIndex = totalSections - 1;
            }

            $previewSections.empty();

            $formSections.each(function(sectionIndex) {
                let $section = $(this);
                let sectionData = {
                    title: $section.find('.form-section-field[data-field="title"]').val() ||
                           $section.find('.form-section-title').text() ||
                           'Sección ' + (sectionIndex + 1),
                    description: $section.find('.form-section-field[data-field="description"]').val() || '',
                    time_limit: $section.find('.form-section-field[data-field="time_limit"]').val() || 0,
                    questions: []
                };
                self.previewData.sections[sectionIndex] = sectionData;

                let $previewSection = $(
                    '<div class="preview-section" data-section-index="' + sectionIndex + '">' +
                    '<h3 class="preview-section-title">' + sectionData.title + '</h3>' +
                    '</div>'
                );

                if (sectionIndex === currentActiveIndex) {
                    $previewSection.addClass('active');
                }

                if (sectionData.description) {
                    $previewSection.append(
                        '<div class="preview-section-description">' + sectionData.description + '</div>'
                    );
                }

                if (parseInt(sectionData.time_limit) > 0) {
                    $previewSection.append(
                        '<div class="preview-section-time">' +
                        '<span class="dashicons dashicons-clock"></span> ' +
                        'Límite de tiempo: ' + sectionData.time_limit + ' minutos' +
                        '</div>'
                    );
                }

                let $previewQuestionsContainer = $('<div class="preview-questions"></div>');

                $section.find('.form-question').each(function(questionIndex) {
                    let $question = $(this);
                    let questionData = {
                        type: $question.attr('data-question-type') || $question.find('.question-type-select').val() || 'text',
                        text: $question.find('.form-question-field[data-field="text"]').val() || $question.find('.form-question-text').text() || 'Pregunta ' + (questionIndex + 1),
                        required: $question.find('.form-question-field[data-field="required"]').prop('checked'),
                        hint: $question.find('.form-question-field[data-field="hint"]').val() || '',
                        explanation: $question.find('.form-question-field[data-field="explanation"]').val() || '',
                        max_chars: $question.find('.form-question-field[data-field="max_chars"]').val() || 0,
                        title_size: $question.find('.form-question-field[data-field="title_size"]').val() || 'h2',
                        title_alignment: $question.find('.form-question-field[data-field="title_alignment"]').val() || 'center',
                        cloze_text: $question.find('.form-question-field[data-field="cloze_text"]').val() || '',
                        case_sensitive: $question.find('.form-question-field[data-field="case_sensitive"]').prop('checked'),
                        statement: $question.find('.form-question-field[data-field="statement"]').val() || '',
                        correct_answer: $question.find('.form-question-field[data-field="correct_answer"]').val() || 'true',
                        feedback_correct: $question.find('.form-question-field[data-field="feedback_correct"]').val() || '',
                        feedback_incorrect: $question.find('.form-question-field[data-field="feedback_incorrect"]').val() || '',
                        image_id: $question.find('.form-question-field[data-field="image_id"]').val() || '',
                        image_url: $question.find('.form-question-field[data-field="image_url"]').val() || '',
                        image_max_chars: $question.find('.form-question-field[data-field="image_max_chars"]').val() || 0,
                        items_text: $question.find('.form-question-field[data-field="items_text"]').val() || '',
                        immediate_feedback: $question.find('.form-question-field[data-field="immediate_feedback"]').prop('checked'),
                        multiple_per_zone: $question.find('.form-question-field[data-field="multiple_per_zone"]').prop('checked'),
                        matching_method: $question.find('.form-question-field[data-field="matching_method"]').val() || 'lines',
                        pairs_text:  $question.find('.form-question-field[data-field="pairs_text"]').val() || '',
                        items_text: $question.find('.form-question-field[data-field="items_text"]').val() || '',
                        options: [],
                        answers: [],
                        drag_items: [],
                        drop_zones: [],
                        pairs: []
                    };

                    self.previewData.sections[sectionIndex].questions[questionIndex] = questionData;

                    if (['select', 'radio', 'checkbox'].includes(questionData.type)) {
                        $question.find('.form-option').each(function() {
                            let optionText = $(this).find('.option-text').val() || '';
                            let optionCorrect = $(this).find('.option-correct').prop('checked');
                            questionData.options.push({
                                text: optionText,
                                correct: optionCorrect
                            });
                        });
                    }

                    if (questionData.type === 'drag-drop') {
                        $question.find('.drag-item-row').each(function(i) {
                            questionData.drag_items.push({
                                text: $(this).find('.drag-item-text').val() || 'Elemento ' + (i + 1),
                                id: 'item_' + i
                            });
                        });

                        $question.find('.drop-zone-row').each(function(i) {
                            let zoneValidItems = [];
                            $(this).find('.drop-zone-items option:selected').each(function() {
                                zoneValidItems.push(parseInt($(this).val(), 10));
                            });
                            questionData.drop_zones.push({
                                name: $(this).find('.drop-zone-name').val() || 'Zona ' + (i + 1),
                                id: 'zone_' + i,
                                validItems: zoneValidItems
                            });
                        });
                    }

                    if (questionData.type === 'matching') {
                        $question.find('.matching-pair-row').each(function(i) {
                            questionData.pairs.push({
                                left: $(this).find('.matching-left-item').val() || 'Elemento ' + (i + 1),
                                right: $(this).find('.matching-right-item').val() || 'Opción ' + (i + 1)
                            });
                        });
                        questionData.matching_method = $question.find('.form-question-field[data-field="matching_method"]').val() || 'lines';
                    }


                    if (questionData.type === 'cloze') {
                        let clozeText = questionData.cloze_text || '';
                        let matches = [];
                        let regex = /\[(.*?)\]/g;
                        let match;
                        while ((match = regex.exec(clozeText)) !== null) {
                            matches.push({
                                index: matches.length,
                                word: match[1].trim()
                            });
                        }
                        questionData.answers = matches.map(item => ({
                            word: item.word,
                            accepted: [item.word]
                        }));
                    }


                    let $previewQuestion = $(
                        '<div class="preview-question" data-question-index="' + questionIndex + '" data-question-type="' + questionData.type + '">' +
                        '<div class="preview-question-header">' +
                            '<div class="preview-question-number">' + (questionIndex + 1) + '. </div>' +
                            '<div class="preview-question-text-container">' +
                                '<div class="preview-question-text">' + questionData.text + (questionData.required ? ' <span class="required">*</span>' : '') + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="preview-question-content"></div>' +
                        '</div>'
                    );

                    let questionContentHTML = '';

                    switch (questionData.type) {
                        case 'true-false':
                            questionContentHTML = self.renderTrueFalseQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'cloze':
                            questionContentHTML = self.renderClozeQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'ordering':
                            questionContentHTML = self.renderOrderingQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'drag-drop':
                            questionContentHTML = self.renderDragDropQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'matching':
                            questionContentHTML = self.renderMatchingQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'text':
                            questionContentHTML = self.renderTextQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'textarea':
                            questionContentHTML = self.renderTextareaQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'select':
                            questionContentHTML = self.renderSelectQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'radio':
                            questionContentHTML = self.renderRadioQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'checkbox':
                            questionContentHTML = self.renderCheckboxQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'image':
                            questionContentHTML = self.renderImageQuestion(questionData, questionIndex, sectionIndex);
                            break;
                        case 'title':
                        default:
                            questionContentHTML = EnglishLineTest.FormUI.createQuestionContent(questionData);
                    }
                    $previewQuestion.find('.preview-question-content').html(questionContentHTML);


                    if (questionData.hint) {
                        $previewQuestion.find('.preview-question-content').after(
                            '<div class="preview-question-hint">' + questionData.hint + '</div>'
                        );
                    }


                    $previewQuestionsContainer.append($previewQuestion);
                    sectionData.questions.push(questionData);
                });

                $previewSection.append($previewQuestionsContainer);
                $previewSections.append($previewSection);
            });

            if (totalSections > 1) {
                if ($formPreview.find('.preview-nav').length === 0) {
                    $previewSections.after(
                        '<div class="preview-nav" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">' +
                        '<button class="preview-prev-btn" style="padding: 8px 15px; background-color: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; min-width: 100px;">Anterior</button>' +
                        '<div class="preview-dots" style="display: flex; gap: 8px; justify-content: center; flex: 1; margin: 0 15px;"></div>' +
                        '<button class="preview-next-btn" style="padding: 8px 15px; background-color: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; min-width: 100px;">Siguiente</button>' +
                        '</div>'
                    );
                }

                let $dots = $formPreview.find('.preview-dots').empty();

                for (let i = 0; i < totalSections; i++) {
                    $dots.append(
                        '<span class="preview-dot' + (i === currentActiveIndex ? ' active' : '') + '" ' +
                        'data-index="' + i + '" ' +
                        'style="width: 12px; height: 12px; border-radius: 50%; background-color: ' + (i === currentActiveIndex ? '#2271b1' : '#ccc') + '; cursor: pointer; transition: background-color 0.2s;"></span>'
                    );
                }

                $formPreview.find('.preview-nav').show();
                $formPreview.find('.preview-prev-btn').prop('disabled', currentActiveIndex === 0);
                $formPreview.find('.preview-next-btn').prop('disabled', currentActiveIndex === totalSections - 1);

                console.log('FormPreview: Vista previa interactiva actualizada con éxito');
            } else {
                if ($formPreview.find('.preview-nav').length > 0) {
                    $formPreview.find('.preview-nav').hide();
                }
            }
        }
    };

    $(function() {
        setTimeout(function() {
            window.EnglishLineTest.FormPreview.init();
        }, 1000);
    });

})(jQuery);

(function ($) {
    'use strict';

    // Asegurarse de que el namespace exista
    window.EnglishLineTest = window.EnglishLineTest || {};

    EnglishLineTest.FormEvents = {
        /**
         * Vincula eventos a los elementos
         */
        bindEvents: function () {
            let self = this;

            self.$saveFormBtn.on('click', function(e) {
                e.preventDefault();
                EnglishLineTest.FormData.saveForm.call(self);
            });

            self.$titleField.on('input', function() {
                $(document).trigger('englishline_form_field_updated', ['title', $(this).val()]);
            });

            self.$descriptionField.on('input', function() {
                $(document).trigger('englishline_form_field_updated', ['description', $(this).val()]);
            });

            self.$sectionsContainer.on('click', '.form-section-action', function(e) {
                e.preventDefault();
                let $section = $(e.currentTarget).closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let action = $(e.currentTarget).data('action');

                switch (action) {
                    case 'edit':
                        EnglishLineTest.FormUI.toggleSectionEdit.call(self, $section);
                        break;

                    case 'delete':
                        EnglishLineTest.FormData.deleteSection.call(self, sectionIndex);
                        break;

                    case 'toggle':
                        EnglishLineTest.FormUI.toggleSectionContent.call(self, $section);
                        break;
                }
            });

            self.$sectionsContainer.on('click', '.form-question-action', function(e) {
                e.preventDefault();
                let $question = $(e.currentTarget).closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let action = $(e.currentTarget).data('action');

                switch (action) {
                    case 'edit':
                        EnglishLineTest.FormUI.toggleQuestionEdit.call(self, $question);
                        break;

                    case 'delete':
                        EnglishLineTest.FormData.deleteQuestion.call(self, sectionIndex, questionIndex);
                        break;
                }
            });

            self.$sectionsContainer.on('change input', '.form-section-field', function(e) {
                let $section = $(e.currentTarget).closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let field = $(e.currentTarget).data('field');
                let value = $(e.currentTarget).val();

                EnglishLineTest.FormData.updateSectionData.call(self, sectionIndex, field, value);
            });

            self.$sectionsContainer.on('change input', '.form-question-field', function(e) {
                let $question = $(e.currentTarget).closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let field = $(e.currentTarget).data('field');
                let value = $(e.currentTarget).val();

                if (field === 'required' || field === 'case_sensitive') {
                    value = $(e.currentTarget).prop('checked');
                }

                EnglishLineTest.FormData.updateQuestionData.call(self, sectionIndex, questionIndex, field, value);
            });

            self.$sectionsContainer.on('click', '.add-option-btn', function(e) {
                e.preventDefault();
                let $question = $(e.currentTarget).closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');

                EnglishLineTest.FormData.addOptionToQuestion.call(self, sectionIndex, questionIndex);
            });

            self.$sectionsContainer.on('click', '.remove-option-btn', function(e) {
                e.preventDefault();
                let $option = $(e.currentTarget).closest('.form-option');
                let $question = $option.closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let optionIndex = $option.data('option-index');

                EnglishLineTest.FormData.removeOptionFromQuestion.call(self, sectionIndex, questionIndex, optionIndex);
            });

            self.$sectionsContainer.on('input', '.option-text', function(e) {
                let $option = $(e.currentTarget).closest('.form-option');
                let $question = $option.closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let optionIndex = $option.data('option-index');
                let value = $(e.currentTarget).val();

                EnglishLineTest.FormData.updateOptionData.call(self, sectionIndex, questionIndex, optionIndex, 'text', value);
            });

            self.$sectionsContainer.on('change', '.question-type-select', function(e) {
                let $question = $(e.currentTarget).closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let newType = $(e.currentTarget).val();

                EnglishLineTest.FormData.changeQuestionType.call(self, sectionIndex, questionIndex, newType);
            });

            $(document).on('input', '.form-question-field[data-field="cloze_text"]', function() {
                let $question = $(this).closest('.form-question');
                let sectionIndex = $question.closest('.form-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let clozeText = $(this).val();

                EnglishLineTest.FormData.updateQuestionData.call(self, sectionIndex, questionIndex, 'cloze_text', clozeText);
                self.updateClozePreview.call(self, $question, clozeText);
                self.generateClozeAnswerFields.call(self, $question, clozeText, sectionIndex, questionIndex);
            });
            
            self.$sectionsContainer.on('click', '.select-image-btn', function(e) {
                e.preventDefault();
                
                let $button = $(this);
                let $question = $button.closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let $preview = $button.closest('.image-selector').find('.image-preview');
                let $imageIdInput = $button.closest('.image-selector').find('input[data-field="image_id"]');
                let $removeBtn = $button.closest('.image-selector').find('.remove-image-btn');
                
                // Registrar el estado actual para depuración
                console.log('Seleccionando imagen para la pregunta:', {
                    sectionIndex: sectionIndex,
                    questionIndex: questionIndex,
                    currentImageId: $imageIdInput.val()
                });
                
                if (!window.imageMediaFrame) {
                    window.imageMediaFrame = wp.media({
                        title: 'Seleccionar imagen',
                        multiple: false,
                        library: { type: 'image' },
                        button: { text: 'Usar esta imagen' }
                    });
                }
                
                // Reiniciar las selecciones
                window.imageMediaFrame.off('select');
                
                // Cuando se seleccione una imagen
                window.imageMediaFrame.on('select', function() {
                    let attachment = window.imageMediaFrame.state().get('selection').first().toJSON();
                    console.log('Imagen seleccionada:', attachment);
                    
                    // Actualizar el preview
                    $preview.html(`<img src="${attachment.url}" style="max-width:100px;max-height:100px;">`);
                    
                    // Establecer el valor del ID y mostrar botón de eliminar
                    $imageIdInput.val(attachment.id);
                    $removeBtn.show();
                    
                    // Crucial: actualizar los datos del modelo inmediatamente
                    EnglishLineTest.FormData.updateQuestionData.call(
                        self, sectionIndex, questionIndex, 'image_id', attachment.id
                    );

                    // Disparar evento de cambio para cualquier otro listener
                    $(document).trigger('englishline_question_image_selected', [
                        sectionIndex, questionIndex, attachment
                    ]);
                });
                
                window.imageMediaFrame.open();
            });
            
            // Manejar la eliminación de imágenes
            self.$sectionsContainer.on('click', '.remove-image-btn', function(e) {
                e.preventDefault();
                
                let $button = $(this);
                let $question = $button.closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let $preview = $button.closest('.image-selector').find('.image-preview');
                let $imageIdInput = $button.closest('.image-selector').find('input[data-field="image_id"]');
                
                // Limpiar el preview y el valor
                $preview.html('<div class="no-image">Sin imagen</div>');
                $imageIdInput.val('');
                $button.hide();
                
                // Actualizar los datos del modelo
                EnglishLineTest.FormData.updateQuestionData.call(
                    self, sectionIndex, questionIndex, 'image_id', ''
                );
                
            });
        },

        /**
         * Actualiza la vista previa de los textos Cloze
         */
        updateClozePreview: function($question, clozeText) {
            let previewText = clozeText.replace(/\[(.*?)\]/g, '<span class="cloze-blank">[$1]</span>');
            $question.find('.form-question-content').html(`
                <div class="form-question-text">${$question.find('.form-question-field[data-field="text"]').val() || 'Completa el texto'}</div>
                <div class="cloze-preview">${previewText}</div>
            `);
        },

        /**
         * Genera campos de respuesta para textos Cloze
         */
        generateClozeAnswerFields: function($question, clozeText, sectionIndex, questionIndex) {
            let matches = [];
            let match;
            let regex = /\[(.*?)\]/g;

            while ((match = regex.exec(clozeText)) !== null) {
                matches.push({
                    index: matches.length,
                    word: match[1]
                });
            }

            let $answersList = $question.find('.cloze-answers-list');
            if (!$answersList.length) {
                $answersList = $('<div class="cloze-answers-list"></div>');
                $question.find('.form-question-config').append(`
                    <div class="form-field">
                        <label>Respuestas correctas</label>
                        <div class="cloze-answers-list"></div>
                        <p class="field-help">Define las respuestas aceptadas para cada espacio.</p>
                    </div>
                `);
                $answersList = $question.find('.cloze-answers-list');
            }

            $answersList.empty();

            if (matches.length > 0) {
                matches.forEach(function(item) {
                    $answersList.append(`
                        <div class="cloze-answer-item" data-blank-index="${item.index}">
                            <div class="cloze-answer-word">"${item.word}"</div>
                            <div class="cloze-answer-field">
                                <label>Respuestas aceptadas (separadas por coma):</label>
                                <input type="text" class="cloze-answer-input" value="${item.word}"
                                    data-blank-index="${item.index}">
                            </div>
                        </div>
                    `);
                });

                let question = EnglishLineTest.FormData.sectionsData[sectionIndex].questions[questionIndex];
                question.answers = matches.map(item => ({
                    word: item.word,
                    accepted: [item.word]
                }));

                $answersList.closest('.form-field').show();
            } else {
                $answersList.closest('.form-field').hide();
            }
        }
    };
})(jQuery);
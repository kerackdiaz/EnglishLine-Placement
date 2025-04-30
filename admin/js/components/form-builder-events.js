(function ($) {
    'use strict';

    window.EnglishLineTest = window.EnglishLineTest || {};

    EnglishLineTest.FormEvents = {
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
                        $question.children('.form-options-container').remove();
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

                if (field === 'required' || field === 'caseSensitive' || field === 'isGradable') {
                    value = $(e.currentTarget).prop('checked');
                }

                EnglishLineTest.FormData.updateQuestionData.call(self, sectionIndex, questionIndex, field, value);
                
                if (field === 'text' && $question.attr('data-question-type') === 'paragraph') {
                    $question.find('.paragraph-preview').html(value || 'Párrafo informativo');
                }
                
                if (field === 'paragraphAlignment') {
                    $question.find('.paragraph-preview')
                        .removeClass('align-left align-center align-right align-justify')
                        .addClass('align-' + value);
                }
                
                if (field === 'isGradable') {
                    $question.find('.grading-specific-fields').toggle(value);
                }
                
                if (field === 'correctOption' && value >= 0) {
                    $question.find('.option-correct').prop('checked', false);
                    $question.find(`.form-option[data-option-index="${value}"] .option-correct`).prop('checked', true);
                }
            });

            self.$sectionsContainer.on('change', '.option-correct', function(e) {
                let $option = $(e.currentTarget).closest('.form-option');
                let $question = $option.closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let optionIndex = $option.data('option-index');
                let isChecked = $(e.currentTarget).prop('checked');
                let questionType = $question.attr('data-question-type');

                if (isChecked) {
                    EnglishLineTest.FormData.updateQuestionData.call(
                        self, sectionIndex, questionIndex, 'isGradable', true
                    );
                }
                
                if ((questionType === 'radio' || questionType === 'select') && isChecked) {
                    $question.find('.option-correct').not($(e.currentTarget)).prop('checked', false);
                    
                    EnglishLineTest.FormData.updateQuestionData.call(
                        self, sectionIndex, questionIndex, 'correctOption', optionIndex
                    );
                    
                    $question.find('.form-option').each(function(i, el) {
                        let optIdx = $(el).data('option-index');
                        EnglishLineTest.FormData.updateOptionData.call(
                            self, sectionIndex, questionIndex, optIdx, 'correct', optIdx === optionIndex
                        );
                    });
                } 
                else if (questionType === 'checkbox') {
                    EnglishLineTest.FormData.updateOptionData.call(
                        self, sectionIndex, questionIndex, optionIndex, 'correct', isChecked
                    );
                    
                    let correctOptions = [];
                    $question.find('.option-correct:checked').each(function() {
                        let optIdx = $(this).closest('.form-option').data('option-index');
                        correctOptions.push(optIdx);
                    });
                    
                    EnglishLineTest.FormData.updateQuestionData.call(
                        self, sectionIndex, questionIndex, 'correctOptions', correctOptions
                    );
                }
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

            $(document).on('input', '.form-question-field[data-field="clozeText"]', function() {
                let $question = $(this).closest('.form-question');
                let sectionIndex = $question.closest('.form-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let clozeText = $(this).val();

                EnglishLineTest.FormData.updateQuestionData.call(self, sectionIndex, questionIndex, 'clozeText', clozeText);
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
                let $imageIdInput = $button.closest('.image-selector').find('input[data-field="imageId"]');
                let $removeBtn = $button.closest('.image-selector').find('.remove-image-btn');
                
                if (!window.imageMediaFrame) {
                    window.imageMediaFrame = wp.media({
                        title: 'Seleccionar imagen',
                        multiple: false,
                        library: { type: 'image' },
                        button: { text: 'Usar esta imagen' }
                    });
                }
                
                window.imageMediaFrame.off('select');
                
                window.imageMediaFrame.on('select', function() {
                    let attachment = window.imageMediaFrame.state().get('selection').first().toJSON();
                    
                    $preview.html(`<img src="${attachment.url}" style="max-width:100px;max-height:100px;">`);
                    $imageIdInput.val(attachment.id);
                    $removeBtn.show();
                    
                    EnglishLineTest.FormData.updateQuestionData.call(
                        self, sectionIndex, questionIndex, 'imageId', attachment.id
                    );

                    $(document).trigger('englishline_question_image_selected', [
                        sectionIndex, questionIndex, attachment
                    ]);
                });
                
                window.imageMediaFrame.open();
            });
            
            self.$sectionsContainer.on('click', '.remove-image-btn', function(e) {
                e.preventDefault();
                
                let $button = $(this);
                let $question = $button.closest('.form-question');
                let $section = $question.closest('.form-section');
                let sectionIndex = $section.data('section-index');
                let questionIndex = $question.data('question-index');
                let $preview = $button.closest('.image-selector').find('.image-preview');
                let $imageIdInput = $button.closest('.image-selector').find('input[data-field="imageId"]');
                
                $preview.html('<div class="no-image">Sin imagen</div>');
                $imageIdInput.val('');
                $button.hide();
                
                EnglishLineTest.FormData.updateQuestionData.call(
                    self, sectionIndex, questionIndex, 'imageId', ''
                );
            });
            
            self.$sectionsContainer.on('input', '.form-question-field[data-field="correctAnswer"]', function() {
                let $question = $(this).closest('.form-question');
                let sectionIndex = $question.closest('.form-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let value = $(this).val();

                EnglishLineTest.FormData.updateQuestionData.call(
                    self, sectionIndex, questionIndex, 'correctAnswer', value
                );
            });
            
            self.$sectionsContainer.on('change', '.form-question-field[data-field="correctValue"]', function() {
                let $question = $(this).closest('.form-question');
                let sectionIndex = $question.closest('.form-section').data('section-index');
                let questionIndex = $question.data('question-index');
                let value = $(this).val() === "true";

                EnglishLineTest.FormData.updateQuestionData.call(
                    self, sectionIndex, questionIndex, 'correctValue', value
                );
            });
        },

        updateClozePreview: function($question, clozeText) {
            if (!clozeText) clozeText = 'Texto con [espacios] para completar';
            let clozePreview = clozeText.replace(/\[(.*?)\]/g, '<span class="cloze-blank">[$1]</span>');
            $question.find('.cloze-preview').html(clozePreview);
        },

        generateClozeAnswerFields: function($question, clozeText, sectionIndex, questionIndex) {
            let blanks = [];
            let match;
            let regex = /\[(.*?)\]/g;
            
            while ((match = regex.exec(clozeText)) !== null) {
                blanks.push(match[1]);
            }
            
            EnglishLineTest.FormData.updateQuestionData.call(
                this, sectionIndex, questionIndex, 'correctFills', blanks
            );
        },

         initOptionsForQuestion: function($question, sectionIndex, questionIndex) {
            let questionData = EnglishLineTest.FormData.getQuestionData(sectionIndex, questionIndex);
            
            if (!questionData && EnglishLineTest.FormBuilder?.sectionsData?.[sectionIndex]?.questions?.[questionIndex]) {
                questionData = EnglishLineTest.FormBuilder.sectionsData[sectionIndex].questions[questionIndex];
            }
            
            if (!questionData?.options?.length) return;
            
            $question.find('.form-options-container, .form-field-options, .add-option-btn').remove();
        
            let $qConfig = $question.find('.form-question-config');
            $question.addClass('editing');
            $qConfig.show();
            
            let $optionsField = $qConfig.find('.form-field').filter(function() {
                return $(this).find('label').text().trim() === 'Opciones';
            });
            
            if ($optionsField.length === 0) {
                $optionsField = $('<div class="form-field"><label>Opciones</label></div>');
                $qConfig.append($optionsField);
            }
            
            let $optionsContainer = $('<div class="form-options-container"></div>');
            $optionsField.append($optionsContainer);
            

            $.each(questionData.options, function(index, option) {
                if (EnglishLineTest.FormUI?.createOptionElement) {
                    let $option = EnglishLineTest.FormUI.createOptionElement(option, index);
                    $optionsContainer.append($option);
                    
                    if (option.correct) {
                        $option.find('.option-correct').prop('checked', true);
                    }
                }
            });
            
            $optionsContainer.after('<button type="button" class="add-option-btn">+ Añadir opción</button>');
            
            let tipo = $question.data('question-type');
            if ((tipo === 'radio' || tipo === 'select') && questionData.correctOption >= 0) {
                $optionsContainer.find('.option-correct').prop('checked', false);
                $optionsContainer.find(`.form-option[data-option-index="${questionData.correctOption}"] .option-correct`).prop('checked', true);
            } else if (tipo === 'checkbox' && Array.isArray(questionData.correctOptions)) {
                questionData.correctOptions.forEach(function(optIndex) {
                    $optionsContainer.find(`.form-option[data-option-index="${optIndex}"] .option-correct`).prop('checked', true);
                });
            }
        }
    };
})(jQuery);
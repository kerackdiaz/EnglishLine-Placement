(function ($) {
    'use strict';

    window.EnglishLineTest = window.EnglishLineTest || {};

    EnglishLineTest.FormDragAndDrop = {
        initSortable: function () {
            let self = this;

            try {
                $('.form-component').draggable({
                    helper: 'clone',
                    revert: 'invalid',
                    zIndex: 100
                });

                $('#form-sections-container').droppable({
                    accept: '.form-component[data-type="section"]',
                    hoverClass: 'drop-hover',
                    drop: function (event, ui) {
                        EnglishLineTest.FormData.addSection.call(self);
                    }
                }).sortable({
                    handle: '.form-section-header',
                    items: '.form-section',
                    placeholder: 'form-section-placeholder',
                    tolerance: 'pointer',
                    update: function(event, ui) {
                        self.updateSectionIndices();
                    }
                });

                $(document).on('englishline_section_added', function(e, sectionIndex) {
                    let $container = $('.form-section[data-section-index="' + sectionIndex + '"] .form-questions-container');
                    if ($container.length) {
                        $container.droppable({
                            accept: '.form-component:not([data-type="section"])',
                            hoverClass: 'drop-hover',
                            drop: function (e, ui) {
                                let type = $(ui.draggable).data('type');
                                EnglishLineTest.FormData.addQuestion.call(self, sectionIndex, type);
                            }
                        }).sortable({
                            items: '.form-question',
                            handle: '.form-question-header',
                            placeholder: 'form-question-placeholder',
                            connectWith: '.form-questions-container',
                            tolerance: 'pointer',
                            update: function(event, ui) {
                                self.updateQuestionIndices($(this).closest('.form-section').data('section-index'));
                            }
                        });
                    }
                });
                
                $('.form-questions-container').each(function() {
                    let $container = $(this);
                    let sectionIndex = $container.closest('.form-section').data('section-index');
                    
                    $container.droppable({
                        accept: '.form-component:not([data-type="section"])',
                        hoverClass: 'drop-hover',
                        drop: function (e, ui) {
                            let type = $(ui.draggable).data('type');
                            EnglishLineTest.FormData.addQuestion.call(self, sectionIndex, type);
                        }
                    }).sortable({
                        items: '.form-question',
                        handle: '.form-question-header',
                        placeholder: 'form-question-placeholder',
                        connectWith: '.form-questions-container',
                        tolerance: 'pointer',
                        update: function(event, ui) {
                            self.updateQuestionIndices($(this).closest('.form-section').data('section-index'));
                        }
                    });
                });
            } catch (error) {
                console.error('Error al inicializar sistema de arrastrar y soltar:', error);
            }
        },

        updateSectionIndices: function() {
            let sections = [];
            $('#form-sections-container .form-section').each(function(index) {
                const oldIndex = $(this).attr('data-section-index');
                const newIndex = index;
                
                $(this).attr('data-section-index', newIndex);
                
                const section = EnglishLineTest.FormData.getSection(oldIndex);
                if (section) {
                    sections[newIndex] = section;
                }
            });
            
            EnglishLineTest.FormData.sections = sections;
            EnglishLineTest.FormData.saveFormData();
        },

        updateQuestionIndices: function(sectionIndex) {
            const section = EnglishLineTest.FormData.getSection(sectionIndex);
            if (!section) return;

            const questions = [];
            $(`.form-section[data-section-index="${sectionIndex}"] .form-questions-container .form-question`).each(function(index) {
                const oldIndex = $(this).attr('data-question-index');
                const newIndex = index;
                
                $(this).attr('data-question-index', newIndex);
                
                if (section.questions && section.questions[oldIndex]) {
                    questions[newIndex] = section.questions[oldIndex];
                }
            });
            
            section.questions = questions;
            EnglishLineTest.FormData.saveFormData();
        }
    };
})(jQuery);
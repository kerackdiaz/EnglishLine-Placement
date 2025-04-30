jQuery(function($) {
    'use strict';
    
    window.EnglishLineTest = window.EnglishLineTest || {};
    
    EnglishLineTest.FormBuilder = {
        sectionsData: [],
        
        initialize: function(options) {
            this.$formBuilder = $('#form-builder');
            this.$sectionsContainer = $('#form-sections-container');
            this.$formComponents = $('.form-component');
            this.$saveFormBtn = $('#save-form-btn');
            this.$titleField = $('#form-title');
            this.$descriptionField = $('#form-description');
            
            this.setupFormData();
            this.loadExistingFormData();
            this.initDragAndDrop();
            this.bindEvents();
        },
        
        setupFormData: function() {
            EnglishLineTest.FormData.setupFormData.call(this);
        },
        
        loadExistingFormData: function() {
            EnglishLineTest.FormData.loadExistingFormData.call(this);
        },
        
        initDragAndDrop: function() {
            EnglishLineTest.FormDragAndDrop.initSortable.call(this);
        },
        
        bindEvents: function() {
            EnglishLineTest.FormEvents.bindEvents.call(this);
        },

        loadFormData: function(formData) {
            EnglishLineTest.FormData.loadFormData.call(this, formData);
        },
        
        renderSections: function() {
            if (!this.sectionsData || !this.sectionsData.length) {
                return;
            }
            
            this.$sectionsContainer.empty();
            
            for (let i = 0; i < this.sectionsData.length; i++) {
                let $section = EnglishLineTest.FormUI.createSectionElement(this.sectionsData[i], i);
                this.$sectionsContainer.append($section);
                
                if (this.sectionsData[i].questions && this.sectionsData[i].questions.length) {
                    let $questionsContainer = $section.find('.form-questions-container');
                    $section.find(".form-questions-empty").hide();
                    
                    for (let j = 0; j < this.sectionsData[i].questions.length; j++) {
                        let question = this.sectionsData[i].questions[j];
                        let $question = EnglishLineTest.FormUI.createQuestionElement(question, j);
                        $questionsContainer.append($question);
                    }
                }
                
                $(document).trigger("englishline_section_added", [i]);
            }
        }
    };
    
    $(document).ready(function() {
        if (window.EnglishLineTest && !window.EnglishLineTest._initialized) {
            window.EnglishLineTest.FormBuilder.initialize({
                autoGradingEnabled: true
            });
            window.EnglishLineTest._initialized = true;
        }
    });
});
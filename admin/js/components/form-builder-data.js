(function ($) {
  "use strict";

  window.EnglishLineTest = window.EnglishLineTest || {};
  window.EnglishLineTest.FormBuilder = window.EnglishLineTest.FormBuilder || {};
  

  EnglishLineTest.FormData = {
    gradableTypes: [
      'text', 'textarea', 'select', 'radio', 'checkbox', 'cloze', 'ordering', 'true-false'
    ],
    

    isGradableType: function(type) {
      return EnglishLineTest.FormData.gradableTypes.includes(type);
    },

    getQuestionData: function(sectionIndex, questionIndex) {
      if (this.sectionsData && 
          this.sectionsData[sectionIndex] && 
          this.sectionsData[sectionIndex].questions && 
          this.sectionsData[sectionIndex].questions[questionIndex]) {
        return this.sectionsData[sectionIndex].questions[questionIndex];
      }
      return null;
    },
    
    getSection: function(sectionIndex) {
      if (this.sectionsData && this.sectionsData[sectionIndex]) {
        return this.sectionsData[sectionIndex];
      }
      return null;
    },
    
    renumberOptions: function(sectionIndex, questionIndex) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (!question || !question.options) return;
      
      question.options.forEach((option, idx) => {
        option.text = "Opción " + (idx + 1);
      });
      
      let $section = this.$sectionsContainer.find(
        '.form-section[data-section-index="' + sectionIndex + '"]'
      );
      let $question = $section.find(
        '.form-question[data-question-index="' + questionIndex + '"]'
      );
      
      $question.find('.form-option').each(function(idx) {
        $(this).attr('data-option-index', idx);
        $(this).find('.option-text').val("Opción " + (idx + 1));
      });
    },

    setupFormData: function () {
      if (!this.$formBuilder) {
        this.$formBuilder = $('#form-builder');
      }
      
      if (!this.$sectionsContainer) {
        this.$sectionsContainer = $('#form-sections-container');
      }
      
      try {
        let formId = 0;
        if (this.$formBuilder.length && typeof this.$formBuilder.attr === 'function') {
          formId = this.$formBuilder.attr('data-form-id') || 0;
        }
        this.formId = parseInt(formId, 10) || 0;
      } catch (e) {
        this.formId = 0;
      }
      
      this.sectionsData = this.sectionsData || [];
      
      return this;
    },

    loadExistingFormData: function () {
      if (typeof formDataFromPHP !== "undefined" && formDataFromPHP) {
        try {
          let dataToProcess = formDataFromPHP;
    
          if (typeof formDataFromPHP === "string") {
            try {
              dataToProcess = JSON.parse(formDataFromPHP);
            } catch (parseError) {
              console.error("Error al parsear los datos del formulario:", parseError);
              return;
            }
          }
    
          if (Array.isArray(dataToProcess)) {
            EnglishLineTest.FormData.loadFormData.call(this, dataToProcess);
          }
        } catch (e) {
          console.error("Error al procesar los datos del formulario:", e);
        }
      }
    },

    preloadFormImages: function () {
      let imageIdsToLoad = [];
      let self = this;
    
      if (this.sectionsData && this.sectionsData.length) {
        this.sectionsData.forEach(function (section, sectionIdx) {
          if (section.questions && section.questions.length) {
            section.questions.forEach(function (question, qIdx) {
              if (question.type === "image" && (question.imageId || question.image_id)) {
                let imageId = parseInt(question.imageId || question.image_id, 10);
                if (!imageId) return;
                
                imageIdsToLoad.push(imageId);
                
                // Actualizar la vista previa de la imagen cuando se carga
                if (typeof wp !== 'undefined' && wp.media) {
                  let attachment = wp.media.attachment(imageId);
                  attachment.fetch({
                    success: function() {
                      let $section = self.$sectionsContainer.find(`.form-section[data-section-index="${sectionIdx}"]`);
                      let $question = $section.find(`.form-question[data-question-index="${qIdx}"]`);
                      let $preview = $question.find('.image-preview');
                      
                      if ($preview.length) {
                        $preview.html(`<img src="${attachment.get('url')}" style="max-width:100px;max-height:100px;">`);
                        $question.find('.remove-image-btn').show();
                      }
                    }
                  });
                }
              }
            });
          }
        });
      }
    },

    loadFormData: function (formData) {
      let self = this;
    
      if (!formData || !Array.isArray(formData)) {
        return;
      }
    
      this.sectionsData = formData;
      
      this.sectionsData.forEach((section) => {
        if (section.questions && section.questions.length) {
          section.questions.forEach((question) => {
            try {
              EnglishLineTest.FormData.prepareGradableData.call(EnglishLineTest.FormData, question);
            } catch (e) {}
            
            if (question.time_limit) {
              question.timeLimit = question.time_limit;
              delete question.time_limit;
            }
            
            if (question.title_size) {
              question.titleSize = question.title_size;
              delete question.title_size;
            }
            
            if (question.title_alignment) {
              question.titleAlignment = question.title_alignment;
              delete question.title_alignment;
            }
            
            if (question.paragraph_alignment) {
              question.paragraphAlignment = question.paragraph_alignment;
              delete question.paragraph_alignment;
            }
            
            if (question.paragraph_content) {
              question.paragraphContent = question.paragraph_content;
              delete question.paragraph_content;
            }
            
            if (question.case_sensitive) {
              question.caseSensitive = question.case_sensitive;
              delete question.case_sensitive;
            }
            
            if (question.cloze_text) {
              question.clozeText = question.cloze_text;
              delete question.cloze_text;
            }
            
            if (question.max_chars) {
              question.maxChars = question.max_chars;
              delete question.max_chars;
            }
            
            if (question.feedback_correct) {
              question.feedbackCorrect = question.feedback_correct;
              delete question.feedback_correct;
            }
            
            if (question.feedback_incorrect) {
              question.feedbackIncorrect = question.feedback_incorrect;
              delete question.feedback_incorrect;
            }
            
            if (question.image_id) {
              question.imageId = question.image_id;
              delete question.image_id;
            }
            
            if (question.image_question_text) {
              question.imageQuestionText = question.image_question_text;
              delete question.image_question_text;
            }
            
            if (question.image_max_chars) {
              question.imageMaxChars = question.image_max_chars;
              delete question.image_max_chars;
            }
            
            if (question.items_text) {
              question.itemsText = question.items_text;
              delete question.items_text;
            }
            
            if (question.pairs_text) {
              question.pairsText = question.pairs_text;
              delete question.pairs_text;
            }
            
            if (question.correct_answer !== undefined) {
              question.correctAnswer = question.correct_answer;
              delete question.correct_answer;
            }
            
            if (question.correct_answers) {
              question.correctAnswers = question.correct_answers;
              delete question.correct_answers;
            }
          });
        }
      });
      
      if (!this.$sectionsContainer) {
        this.$sectionsContainer = $('#form-sections-container');
      }
      
      this.$sectionsContainer.empty();
    
      $.each(this.sectionsData, function (index, section) {
        let $section = EnglishLineTest.FormUI.createSectionElement(section, index);
        self.$sectionsContainer.append($section);
        
        if (section.questions && section.questions.length) {
          let $questionsContainer = $section.find('.form-questions-container');
          $section.find(".form-questions-empty").hide();
          
          $.each(section.questions, function (qIndex, question) {
            let typeLabel = EnglishLineTest.questionTypeLabels[question.type] || question.type;
            let $question = $(`
              <div class="form-question" data-question-index="${qIndex}" data-question-type="${question.type}" data-question-id="${question.id || ''}">
                <div class="form-question-header">
                  <span class="form-question-type">${typeLabel}</span>
                  <div class="form-question-text">${question.text || 'Sin texto'}</div>
                  <div class="form-question-actions">
                    <button type="button" class="form-question-action" data-action="edit">
                      <span class="dashicons dashicons-edit"></span>
                    </button>
                    <button type="button" class="form-question-action delete" data-action="delete">
                      <span class="dashicons dashicons-trash"></span>
                    </button>
                  </div>
                </div>
                <div class="form-question-content"></div>
                <div class="form-question-config" style="display: none;">
                  ${EnglishLineTest.FormUI.getQuestionConfigFields ? EnglishLineTest.FormUI.getQuestionConfigFields(question) : ''}
                </div>
              </div>
            `);
            
            $questionsContainer.append($question);
            
            if ((question.type === 'select' || question.type === 'radio' || question.type === 'checkbox') && 
                question.options && question.options.length) {
              EnglishLineTest.FormEvents.initOptionsForQuestion($question, index, qIndex);
            }
          });
        }
        
        $(document).trigger("englishline_section_added", [index]);
      });
    
      setTimeout(function () {
        EnglishLineTest.FormData.preloadFormImages.call(self);
        
        $('.form-question[data-question-type="select"], .form-question[data-question-type="radio"], .form-question[data-question-type="checkbox"]').each(function() {
          let $question = $(this);
          let sectionIndex = $question.closest('.form-section').data('section-index');
          let questionIndex = $question.data('question-index');
          
          if (sectionIndex !== undefined && questionIndex !== undefined) {
            EnglishLineTest.FormEvents.initOptionsForQuestion($question, sectionIndex, questionIndex);
          }
        });
      }, 500);
    
      $(document).trigger("englishline_form_data_loaded", [this.sectionsData]);
    },

    prepareGradableData: function(question) {
      if (!question || !question.type) return;
      
      if (EnglishLineTest.FormData.isGradableType(question.type)) {
        if (question.type === 'cloze' && (question.clozeText || question.cloze_text)) {
          const text = question.clozeText || question.cloze_text;
          const regex = /\[(.*?)\]/g;
          const matches = [];
          let match;
          
          while ((match = regex.exec(text)) !== null) {
            matches.push(match[1]);
          }
          
          if (matches.length > 0) {
            question.correctFills = matches;
            question.isGradable = true;
          }
        }
        
        if (question.type === 'ordering' && (question.itemsText || question.items_text)) {
          const text = question.itemsText || question.items_text;
          const items = text.trim().split('\n').filter(item => item.trim());
          if (items.length > 0) {
            question.correctOrder = Array.from({ length: items.length }, (_, i) => i);
            question.isGradable = true;
          }
        }
        
        if (question.type === 'true-false' && question.correctValue !== undefined) {
          question.isGradable = true;
        }
        
        if ((question.type === 'select' || question.type === 'radio') && 
            question.correctOption !== undefined && question.correctOption > -1) {
          question.isGradable = true;
        }
        
        if (question.type === 'checkbox' && 
            question.correctOptions && Array.isArray(question.correctOptions) && 
            question.correctOptions.length > 0) {
          question.isGradable = true;
        }
        
        if ((question.type === 'text' || question.type === 'textarea') && 
            question.correctAnswer !== undefined && question.correctAnswer !== '') {
          question.isGradable = true;
        }
      }
    },

    addSection: function (sectionData) {
      this.$sectionsContainer.find(".form-sections-empty").remove();
    
      let newSectionIndex = this.sectionsData.length;
      let newSection = sectionData || {
        title: "Nueva Sección",
        description: "",
        questions: [],
      };
    
      this.sectionsData.push(newSection);
      
      try {
        let $section = EnglishLineTest.FormUI.createSectionElement(newSection, newSectionIndex);
        if (!$section) {
          return -1;
        }
        
        this.$sectionsContainer.append($section);
        
        $(document).trigger("englishline_section_added", [newSectionIndex]);
        $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
        
        return newSectionIndex;
      } catch (error) {
        return -1;
      }
    },

    addQuestion: function (sectionIndex, questionType, questionData) {
      if (!this.sectionsData[sectionIndex]) {
        return -1;
      }
      if (!this.sectionsData[sectionIndex].questions) {
        this.sectionsData[sectionIndex].questions = [];
      }

      let $section = this.$sectionsContainer.find(
        '.form-section[data-section-index="' + sectionIndex + '"]'
      );
      $section.find(".form-questions-empty").hide();

      let questionIndex = this.sectionsData[sectionIndex].questions.length;
      let newQuestion = questionData || {
        id: 'q_' + Date.now() + '_' + Math.floor(Math.random() * 1000), // Añadir ID único
        type: questionType,
        text: EnglishLineTest.FormUtils.getDefaultQuestionTitle(questionType),
        required: false,
        hint: "",
        explanation: "",
        isGradable: EnglishLineTest.FormData.isGradableType(questionType)
      };
      
      if (!questionData) {
        switch (questionType) {
          case 'text':
          case 'textarea':
            newQuestion.correctAnswer = '';
            newQuestion.caseSensitive = false;
            break;
            
          case 'radio':
          case 'select':
            newQuestion.options = [];
            newQuestion.correctOption = -1;
            break;
            
          case 'checkbox':
            newQuestion.options = [];
            newQuestion.correctOptions = [];
            break;
            
          case 'cloze':
            newQuestion.clozeText = '';
            newQuestion.correctFills = [];
            newQuestion.caseSensitive = false;
            break;
            
          case 'ordering':
            newQuestion.itemsText = '';
            newQuestion.correctOrder = [];
            break;
            
          case 'true-false':
            newQuestion.statement = '';
            newQuestion.correctValue = true;
            newQuestion.feedbackCorrect = '';
            newQuestion.feedbackIncorrect = '';
            newQuestion.isGradable = true;
            break;
            
          case 'image':
            newQuestion.imageId = '';
            newQuestion.imageQuestionText = '';
            newQuestion.imageMaxChars = 500;
            break;
            
          case 'title':
            newQuestion.titleSize = 'h2';
            newQuestion.titleAlignment = 'center';
            break;
            
          case 'paragraph':
            newQuestion.paragraphAlignment = 'left';
            newQuestion.paragraphContent = '';
            break;
        }
      }

      this.sectionsData[sectionIndex].questions.push(newQuestion);
      
      try {
        let typeLabel = EnglishLineTest.questionTypeLabels[questionType] || questionType;
        let $question = $(`
          <div class="form-question" data-question-index="${questionIndex}" data-question-type="${questionType}" data-question-id="${newQuestion.id  || ''}">
            <div class="form-question-header">
              <span class="form-question-type">${typeLabel}</span>
              <div class="form-question-text">${newQuestion.text || 'Sin texto'}</div>
              <div class="form-question-actions">
                <button type="button" class="form-question-action" data-action="edit">
                  <span class="dashicons dashicons-edit"></span>
                </button>
                <button type="button" class="form-question-action delete" data-action="delete">
                  <span class="dashicons dashicons-trash"></span>
                </button>
              </div>
            </div>
            <div class="form-question-content"></div>
            <div class="form-question-config" style="display: none;">
              ${EnglishLineTest.FormUI.getQuestionConfigFields ? EnglishLineTest.FormUI.getQuestionConfigFields(newQuestion) : ''}
            </div>
          </div>
        `);
        
        $section.find('.form-questions-container').append($question);
        
        if ((questionType === 'select' || questionType === 'radio' || questionType === 'checkbox')) {
          EnglishLineTest.FormEvents.initOptionsForQuestion($question, sectionIndex, questionIndex);
        }
        
        $(document).trigger("englishline_question_added", [sectionIndex, questionIndex, questionType]);
        $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
        
        return questionIndex;
      } catch (error) {
        return -1;
      }
    },

    addOptionToQuestion: function (sectionIndex, questionIndex) {
      if (!this.sectionsData[sectionIndex] ||
          !this.sectionsData[sectionIndex].questions ||
          !this.sectionsData[sectionIndex].questions[questionIndex]) {
        return;
      }

      let question = this.sectionsData[sectionIndex].questions[questionIndex];

      if (!["select", "radio", "checkbox"].includes(question.type)) {
        return;
      }

      if (!question.options) {
        question.options = [];
      }

      let optionIndex = question.options.length;
      let newOption = {
        text: "Opción " + (optionIndex + 1),
        correct: false,
      };

      question.options.push(newOption);

      let $section = this.$sectionsContainer.find(
        '.form-section[data-section-index="' + sectionIndex + '"]'
      );
      let $question = $section.find(
        '.form-question[data-question-index="' + questionIndex + '"]'
      );
      let $optionsContainer = $question.find(".form-question-config .form-options-container");
      if ($optionsContainer.length === 0) {
        $optionsContainer = $('<div class="form-options-container"></div>');
        $question.find('.form-field:contains("Opciones")').append($optionsContainer);
      }
      
      let $newOption = EnglishLineTest.FormUI.createOptionElement(newOption, optionIndex);
      $optionsContainer.append($newOption);

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
      
      return optionIndex;
    },

    removeOptionFromQuestion: function (sectionIndex, questionIndex, optionIndex) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (!question || !question.options || !question.options[optionIndex]) {
        return;
      }

      question.options.splice(optionIndex, 1);

      let $section = this.$sectionsContainer.find(
        '.form-section[data-section-index="' + sectionIndex + '"]'
      );
      let $question = $section.find(
        '.form-question[data-question-index="' + questionIndex + '"]'
      );
      $question
        .find('.form-option[data-option-index="' + optionIndex + '"]')
        .remove();

      this.renumberOptions(sectionIndex, questionIndex);
      
      if (question.type === "checkbox" && question.correctOptions) {
        question.correctOptions = question.correctOptions
          .filter(index => index !== optionIndex)
          .map(index => index > optionIndex ? index - 1 : index);
      } else if ((question.type === "radio" || question.type === "select") && 
                question.correctOption === optionIndex) {
        question.correctOption = -1;
      } else if ((question.type === "radio" || question.type === "select") && 
                question.correctOption > optionIndex) {
        question.correctOption--;
      }

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },
    
    deleteSection: function (sectionIndex) {
      this.sectionsData.splice(sectionIndex, 1);

      this.$sectionsContainer
        .find('.form-section[data-section-index="' + sectionIndex + '"]')
        .remove();

      let formUtils = EnglishLineTest.FormUtils;
      if (formUtils && typeof formUtils.updateSectionIndices === 'function') {
        formUtils.updateSectionIndices.call(this);
      }

      if (this.sectionsData.length === 0) {
        this.$sectionsContainer.html(`
          <div class="form-sections-empty">
            <p>Arrastra componentes aquí para construir tu formulario.</p>
            <p>Primero añade una sección y luego preguntas dentro de ella.</p>
          </div>
        `);
      }

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },

    deleteQuestion: function (sectionIndex, questionIndex) {
      this.sectionsData[sectionIndex].questions.splice(questionIndex, 1);

      let $section = this.$sectionsContainer.find(
        '.form-section[data-section-index="' + sectionIndex + '"]'
      );
      $section
        .find('.form-question[data-question-index="' + questionIndex + '"]')
        .remove();

      let formUtils = EnglishLineTest.FormUtils;
      if (formUtils && typeof formUtils.updateQuestionIndices === 'function') {
        formUtils.updateQuestionIndices.call(this, sectionIndex);
      }

      if (this.sectionsData[sectionIndex].questions.length === 0) {
        $section.find(".form-questions-container").empty();
        $section.find(".form-questions-empty").show();
      }

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },

    updateSectionData: function (sectionIndex, field, value) {
      if (this.sectionsData[sectionIndex]) { 
        this.sectionsData[sectionIndex][field] = value; 
        $(document).trigger("englishline_form_data_changed", [this.sectionsData]); 
      }
    },

    updateQuestionData: function (sectionIndex, questionIndex, field, value) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (!question) return;
      
      switch (field) {
        case "required":
        case "caseSensitive":
        case "isGradable":
        case "case_sensitive":
          question[field === "case_sensitive" ? "caseSensitive" : field] = !!value;
          break;
          
        case "correctValue":
        case "correct_answer":
          question[field === "correct_answer" ? "correctAnswer" : field] = value === "true" || value === true;
          break;
          
        case "imageId":
        case "image_id":
          question[field === "image_id" ? "imageId" : field] = value ? parseInt(value, 10) || value : "";
          break;
          
        case "clozeText":
        case "cloze_text": {
          const fieldName = field === "cloze_text" ? "clozeText" : field;
          question[fieldName] = value;
          
          const regex = /\[(.*?)\]/g;
          const matches = [];
          let match;
          
          while ((match = regex.exec(value)) !== null) {
            matches.push(match[1]);
          }
          
          question.correctFills = matches;
          question.isGradable = matches.length > 0;
          break;
        }
          
        case "itemsText":
        case "items_text": {
          const fieldName = field === "items_text" ? "itemsText" : field;
          question[fieldName] = value;
          
          if (question.type === "ordering") {
            const items = value.trim().split('\n').filter(item => item.trim());
            question.correctOrder = Array.from({ length: items.length }, (_, i) => i);
            question.isGradable = items.length > 0;
          }
          break;
        }
          
        case "correctOption":
          question[field] = parseInt(value, 10);
          question.isGradable = parseInt(value, 10) > -1;
          break;
          
        case "correctOptions":
          question[field] = Array.isArray(value) ? value : [];
          question.isGradable = Array.isArray(value) && value.length > 0;
          break;
          
        case "correctAnswer":
          question[field] = value;
          question.isGradable = value !== undefined && value !== null && value !== '';
          break;
          
        case "feedbackCorrect":
        case "feedback_correct":
          question[field === "feedback_correct" ? "feedbackCorrect" : field] = value;
          break;
          
        case "feedbackIncorrect":  
        case "feedback_incorrect":
          question[field === "feedback_incorrect" ? "feedbackIncorrect" : field] = value;
          break;
          
        case "maxChars":
        case "max_chars":
          question[field === "max_chars" ? "maxChars" : field] = parseInt(value, 10) || 0;
          break;
          
        case "titleSize":
        case "title_size":
          question[field === "title_size" ? "titleSize" : field] = value;
          break;
          
        case "titleAlignment":
        case "title_alignment":
          question[field === "title_alignment" ? "titleAlignment" : field] = value;
          break;
          
        case "paragraphAlignment":  
        case "paragraph_alignment":
          question[field === "paragraph_alignment" ? "paragraphAlignment" : field] = value;
          break;
        
          case "paragraphContent":
            case "content":  
              question["paragraphContent"] = value;
              break;  
          
        case "imageQuestionText":
        case "image_question_text":
          question[field === "image_question_text" ? "imageQuestionText" : field] = value;
          break;
          
        case "imageMaxChars":
        case "image_max_chars":
          question[field === "image_max_chars" ? "imageMaxChars" : field] = parseInt(value, 10) || 0;
          break;
          
        default:
          question[field] = value;
      }

      if (field === "text" || field === "statement") {
        let $section = this.$sectionsContainer.find(
          '.form-section[data-section-index="' + sectionIndex + '"]'
        );
        let $question = $section.find(
          '.form-question[data-question-index="' + questionIndex + '"]'
        );
        $question.find(".form-question-text").text(value || "Sin texto");
      }

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },

    updateOptionData: function (sectionIndex, questionIndex, optionIndex, field, value) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (!question || !question.options || !question.options[optionIndex]) return;
      
      question.options[optionIndex][field] = value;
      
      if (field === "correct" && value === true) {
        if (question.type === "radio" || question.type === "select") {
          question.options.forEach((opt, idx) => {
            if (idx !== optionIndex) {
              opt.correct = false;
            }
          });
          question.correctOption = optionIndex;
          question.isGradable = true;
        } else if (question.type === "checkbox") {
          if (!question.correctOptions) {
            question.correctOptions = [];
          }
          
          if (!question.correctOptions.includes(optionIndex)) {
            question.correctOptions.push(optionIndex);
            question.correctOptions.sort((a, b) => a - b);
          }
          question.isGradable = true;
        }
      } else if (field === "correct" && value === false && question.type === "checkbox") {
        if (question.correctOptions && question.correctOptions.includes(optionIndex)) {
          question.correctOptions = question.correctOptions.filter(idx => idx !== optionIndex);
          question.isGradable = question.correctOptions.length > 0;
        }
      } else if (field === "correct" && value === false && 
                (question.type === "radio" || question.type === "select") && 
                question.correctOption === optionIndex) {
        question.correctOption = -1;
        question.isGradable = false;
      }
      
      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },
    
    saveForm: function () {
      let self = this;
      
      for (let i = 0; i < this.sectionsData.length; i++) {
        let section = this.sectionsData[i];
        if (section.questions) {
          for (let j = 0; j < section.questions.length; j++) {
            let q = section.questions[j];
            try {
              EnglishLineTest.FormData.prepareGradableData.call(EnglishLineTest.FormData, q);
            } catch (e) {}
            
            if (q.type === 'image') {
              q.imageId = q.imageId ? parseInt(q.imageId, 10) || q.imageId : '';
            }
          }
        }
      }
      
      let formTitle = this.$titleField.val().trim();
      let formDescription = this.$descriptionField.val().trim();
    
      if (!formTitle) {
        alert("Por favor, introduce un título para el examen.");
        this.$titleField.focus();
        return;
      }
    
      if (this.sectionsData.length === 0) {
        alert("El examen debe tener al menos una sección.");
        return;
      }
    
      let emptySections = [];
      $.each(this.sectionsData, function (index, section) {
        if (!section.questions || section.questions.length === 0) {
          emptySections.push(section.title || "Sección " + (index + 1));
        }
      });
    
      if (emptySections.length > 0) {
        alert("Las siguientes secciones no tienen preguntas: " + emptySections.join(", "));
        return;
      }
    
      try {
        let formData = {
          title: formTitle,
          description: formDescription,
          sections: JSON.parse(JSON.stringify(this.sectionsData))
        };
        localStorage.setItem('englishline_form_backup', JSON.stringify(formData));
      } catch (e) {}
    
      let formId = parseInt(this.formId, 10) || 0;
      let isNewForm = formId === 0;
      let buttonText = isNewForm ? "Guardando..." : "Actualizando...";
      this.$saveFormBtn.prop("disabled", true).text(buttonText);
    
      if (typeof window.englishline_test === "undefined") {
        alert("Error: No se puede guardar el formulario debido a un problema en la configuración.");
        this.$saveFormBtn.prop("disabled", false).text(isNewForm ? "Guardar Examen" : "Actualizar Examen");
        return;
      }
    
      $.ajax({
        url: window.englishline_test.ajax_url,
        type: "POST",
        data: {
          action: "englishline_save_form",
          nonce: window.englishline_test.nonce,
          title: formTitle,
          description: formDescription,
          form_id: formId,
          form_data: JSON.stringify(this.sectionsData),
          status: 'publish',
        },
        success: function (response) {
          if (response.success) {
            let successMessage = isNewForm 
              ? "El examen se ha guardado correctamente." 
              : "El examen se ha actualizado correctamente.";
            
            if (isNewForm && response.data && response.data.form_id) {
              let newFormId = response.data.form_id;
              
              self.formId = newFormId;
              self.$formBuilder.attr("data-form-id", newFormId);
              
              if (history.pushState) {
                let baseUrl = window.location.href.split('?')[0];
                let newUrl = baseUrl + '?page=englishline-test-forms&action=edit&form_id=' + newFormId;
                history.pushState({formId: newFormId}, document.title, newUrl);
              }
              
              localStorage.setItem('englishline_last_form_id', newFormId);
              
              if ($('#form-shortcode').length > 0) {
                $('#form-shortcode').val('[englishline_test id=' + newFormId + ']');
                $('#form-shortcode-wrapper').show();
              }
            }
            
            alert(successMessage);
          } else {
            alert("Error al guardar el examen: " + (response.data ? response.data.message : "Error desconocido"));
          }
        },
        error: function () {
          alert("Error de conexión al guardar el examen. Por favor, inténtalo de nuevo.");
        },
        complete: function () {
          self.$saveFormBtn.prop("disabled", false).text("Actualizar Examen");
        }
      });
    }
  };
  
  $(document).ready(function() {
    setTimeout(function() {
      $('.form-question[data-question-type="select"], .form-question[data-question-type="radio"], .form-question[data-question-type="checkbox"]').each(function() {
        let $question = $(this);
        let sectionIndex = $question.closest('.form-section').data('section-index');
        let questionIndex = $question.data('question-index');
        
        if (sectionIndex !== undefined && questionIndex !== undefined) {
          $question.addClass('editing');
          $question.find('.form-question-config').show();
          EnglishLineTest.FormEvents.initOptionsForQuestion($question, sectionIndex, questionIndex);
        }
      });
    }, 1000);
  });
  
  window.reiniciarTodasLasOpciones = function() {
    $('.form-question[data-question-type="select"], .form-question[data-question-type="radio"], .form-question[data-question-type="checkbox"]').each(function() {
      let $question = $(this);
      let sectionIndex = $question.closest('.form-section').data('section-index');
      let questionIndex = $question.data('question-index');
      
      $question.addClass('editing');
      $question.find('.form-question-config').show();
      EnglishLineTest.FormEvents.initOptionsForQuestion($question, sectionIndex, questionIndex);
    });
    
    return "Recuperación completada";
  };
})(jQuery);
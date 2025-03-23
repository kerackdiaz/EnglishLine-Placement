(function ($) {
  "use strict";

  // Asegurarse de que el namespace exista
  window.EnglishLineTest = window.EnglishLineTest || {};

  EnglishLineTest.FormData = {
    /**
     * Configura los datos iniciales del formulario
     */
    setupFormData: function () {
      this.formId = this.$formBuilder.data("form-id") || 0;
      this.sectionsData = [];
    },

    /**
     * Carga los datos de un formulario existente desde PHP
     */
    loadExistingFormData: function () {
      let self = this;
      if (typeof formDataFromPHP !== "undefined" && formDataFromPHP) {
        try {
           let dataToProcess = formDataFromPHP;

          if (typeof formDataFromPHP === "string") {
            if (formDataFromPHP.indexOf("\\") !== -1) {
              try {
                dataToProcess = JSON.parse(
                  formDataFromPHP.replace(/\\\"/g, '"')
                );
              } catch (e) {
                dataToProcess = JSON.parse(formDataFromPHP);
              }
            } else {
              dataToProcess = JSON.parse(formDataFromPHP);
            }
          }


          if (Array.isArray(dataToProcess)) {
            EnglishLineTest.FormData.loadFormData.call(EnglishLineTest.FormBuilder, dataToProcess);
          } 
        } catch (e) {
          console.error(
            "Error al parsear los datos del formulario desde PHP:",
            e
          );
          console.log("Datos sin parsear desde PHP:", formDataFromPHP);
        }
      } else {
        console.log(
          "No se encontraron datos de formulario existentes desde PHP o formDataFromPHP está vacío."
        );
      }
    },

    /**
     * Precarga las imágenes para asegurar que estén disponibles
     */
    preloadFormImages: function () {
      let self = this;
      let imageIdsToLoad = [];

      // Recopilar todos los IDs de imágenes del formulario
      if (this.sectionsData && this.sectionsData.length) {
        this.sectionsData.forEach(function (section) {
          if (section.questions && section.questions.length) {
            section.questions.forEach(function (question) {
              if (question.type === "image" && question.image_id) {
                imageIdsToLoad.push(parseInt(question.image_id, 10));
              }
            });
          }
        });
      }

      if (imageIdsToLoad.length > 0) {
        console.log("Precargando imágenes con IDs:", imageIdsToLoad);


        imageIdsToLoad.forEach(function (imageId) {
          if (!imageId) return;


          wp.media.attachment(imageId).fetch({
            success: function (data) {
              console.log("Imagen precargada con éxito:", imageId, data);
            },
            error: function (error) {
              console.error("Error al precargar imagen:", imageId, error);
            },
          });
        });
      }
    },

    /**
     * Carga los datos de un formulario existente
     *
     * @param {Object} formData - Los datos del formulario a cargar
     */
    
    loadFormData: function (formData) {
      let self = this;
    
      if (!formData || !Array.isArray(formData)) {
        return;
      }
    
      this.sectionsData = formData;
    
      if (this.$sectionsContainer) {
        this.$sectionsContainer.empty();
      } else {
        return;
      }
    
      $.each(this.sectionsData, function (index, section) {
        EnglishLineTest.FormBuilder.addSectionToUI.call(
          EnglishLineTest.FormBuilder,
          section,
          index
        );
      });
    
      setTimeout(function () {

        if (typeof EnglishLineTest.FormData.preloadFormImages === 'function') {
          EnglishLineTest.FormData.preloadFormImages.call(EnglishLineTest.FormData);
        } 
      }, 500);
    
      $(document).trigger("englishline_form_data_loaded", [this.sectionsData]);
    },

    /**
     * Añade una nueva sección al formulario
     */
    addSection: function (sectionData) {
      this.$sectionsContainer.find(".form-sections-empty").remove();

      let newSectionIndex = this.sectionsData.length;
      let newSection = sectionData || {
        title: "Nueva Sección",
        description: "",
        questions: [],
      };

      this.sectionsData.push(newSection);
      EnglishLineTest.FormBuilder.addSectionToUI.call(
        EnglishLineTest.FormBuilder,
        newSection,
        newSectionIndex
      );
      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);

      return newSectionIndex;
    },

    /**
     * Añade una nueva pregunta a una sección
     */
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
        type: questionType,
        text: EnglishLineTest.FormUtils.getDefaultQuestionTitle(questionType),
        required: false,
        hint: "",
        explanation: "",
        options: [],
        correct_answer: null,
        correct_answers: [],
        items: [],
        pairs: [],
        drop_zones: [],
        cloze_text: "",
        case_sensitive: false,
        statement: "",
        feedback_correct: "",
        feedback_incorrect: "",
        image_id: "",
        image_question_text: "",
        image_max_chars: 0,
        items_text: "",
        pairs_text: "",
        drag_items: [],
      };

      if (questionType === "image" && !questionData) {
        newQuestion.image_id = "";
        newQuestion.image_question_text = "";
        newQuestion.image_max_chars = 500;
      }

      if (
        ["select", "radio", "checkbox"].includes(questionType) &&
        (!questionData || !questionData.options)
      ) {
        newQuestion.options = [
          { text: "Opción 1", correct: false },
          { text: "Opción 2", correct: false },
        ];
      }

      this.sectionsData[sectionIndex].questions.push(newQuestion);
      EnglishLineTest.FormBuilder.addQuestionToUI.call(
        EnglishLineTest.FormBuilder,
        sectionIndex,
        newQuestion,
        questionIndex
      );
      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);

      return questionIndex;
    },

    /**
     * Añade una nueva opción a una pregunta
     */
    addOptionToQuestion: function (sectionIndex, questionIndex) {
      if (
        !this.sectionsData[sectionIndex] ||
        !this.sectionsData[sectionIndex].questions ||
        !this.sectionsData[sectionIndex].questions[questionIndex]
      ) {
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
      let $optionsContainer = $question.find(".form-options-container");
      let $newOption = EnglishLineTest.FormUI.createOptionElement.call(
        this,
        newOption,
        optionIndex
      );
      $optionsContainer.append($newOption);

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },

    /**
     * Elimina una opción de una pregunta
     */
    removeOptionFromQuestion: function (
      sectionIndex,
      questionIndex,
      optionIndex
    ) {
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

      EnglishLineTest.FormUtils.reindexOptions.call(
        this,
        sectionIndex,
        questionIndex
      );

      $(document).trigger("englishline_form_data_changed", [this.sectionsData]);
    },
    /**
     * Elimina una sección
     */
    deleteSection: function (sectionIndex) {
      if (
        confirm(
          "¿Estás seguro de que deseas eliminar esta sección y todas sus preguntas?"
        )
      ) {
        this.sectionsData.splice(sectionIndex, 1);

        this.$sectionsContainer
          .find('.form-section[data-section-index="' + sectionIndex + '"]')
          .remove();

        EnglishLineTest.FormUtils.updateSectionIndices.call(this);

        if (this.sectionsData.length === 0) {
          this.$sectionsContainer.html(`
                            <div class="form-sections-empty">
                                <p>Arrastra componentes aquí para construir tu formulario.</p>
                                <p>Primero añade una sección y luego preguntas dentro de ella.</p>
                            </div>
                        `);
        }

        $(document).trigger("englishline_form_data_changed", [
          this.sectionsData,
        ]);
      }
    },

    /**
     * Elimina una pregunta
     */
    deleteQuestion: function (sectionIndex, questionIndex) {
      if (confirm("¿Estás seguro de que deseas eliminar esta pregunta?")) {
        this.sectionsData[sectionIndex].questions.splice(questionIndex, 1);

        let $section = this.$sectionsContainer.find(
          '.form-section[data-section-index="' + sectionIndex + '"]'
        );
        $section
          .find('.form-question[data-question-index="' + questionIndex + '"]')
          .remove();

        EnglishLineTest.FormUtils.updateQuestionIndices.call(
          this,
          sectionIndex
        );

        if (this.sectionsData[sectionIndex].questions.length === 0) {
          $section.find(".form-questions-container").empty();
          $section.find(".form-questions-empty").show();
        }

        $(document).trigger("englishline_form_data_changed", [
          this.sectionsData,
        ]);
      }
    },

    /**
     * Cambia el tipo de una pregunta
     */
    changeQuestionType: function (sectionIndex, questionIndex, newType) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (!question) {
        return;
      }

      switch (newType) {
        case "text":
        case "textarea":
        case "title":
          delete question.options;
          delete question.correct_answer;
          delete question.correct_answers;
          delete question.items;
          delete question.pairs;
          delete question.drop_zones;
          delete question.cloze_text;
          delete question.case_sensitive;
          delete question.statement;
          delete question.feedback_correct;
          delete question.feedback_incorrect;
          delete question.image_id;
          delete question.image_question_text;
          delete question.image_max_chars;
          delete question.items_text;
          delete question.pairs_text;
          break;

        case "select":
        case "radio":
        case "checkbox":
          delete question.correct_answer;
          delete question.correct_answers;
          delete question.items;
          delete question.pairs;
          delete question.drop_zones;
          delete question.cloze_text;
          delete question.case_sensitive;
          delete question.statement;
          delete question.feedback_correct;
          delete question.feedback_incorrect;
          delete question.image_id;
          delete question.image_question_text;
          delete question.image_max_chars;
          delete question.items_text;
          delete question.pairs_text;
          break;

        case "cloze":
          delete question.options;
          delete question.correct_answer;
          delete question.correct_answers;
          delete question.items;
          delete question.pairs;
          delete question.drop_zones;
          delete question.statement;
          delete question.feedback_correct;
          delete question.feedback_incorrect;
          delete question.image_id;
          delete question.image_question_text;
          delete question.image_max_chars;
          delete question.items_text;
          delete question.pairs_text;
          break;

        case "true-false":
          delete question.options;
          delete question.correct_answers;
          delete question.items;
          delete question.pairs;
          delete question.drop_zones;
          delete question.cloze_text;
          delete question.case_sensitive;
          delete question.image_id;
          delete question.image_question_text;
          delete question.image_max_chars;
          delete question.items_text;
          delete question.pairs_text;
          break;

        case "image":
          let existingImageId = question.image_id || "";
          console.log('Cambiando a tipo imagen, preservando ID:', existingImageId);
          delete question.options;
          delete question.correct_answer;
          delete question.correct_answers;
          delete question.items;
          delete question.pairs;
          delete question.drop_zones;
          delete question.cloze_text;
          delete question.case_sensitive;
          delete question.statement;
          delete question.feedback_correct;
          delete question.feedback_incorrect;
          delete question.items_text;
          delete question.pairs_text;

          question.image_id = existingImageId;
          if (!question.image_question_text) question.image_question_text = "";
          if (!question.image_max_chars) question.image_max_chars = 500;
          break;

        case "ordering":
          delete question.options;
          delete question.correct_answer;
          delete question.correct_answers;
          delete question.cloze_text;
          delete question.case_sensitive;
          delete question.statement;
          delete question.feedback_correct;
          delete question.feedback_incorrect;
          delete question.image_id;
          delete question.image_question_text;
          delete question.image_max_chars;
          delete question.pairs_text;
          break;
      }

      question.type = newType;

      let $section = this.$sectionsContainer.find(
        '.form-section[data-section-index="' + sectionIndex + '"]'
      );
      let $question = $section.find(
        '.form-question[data-question-index="' + questionIndex + '"]'
      );
      $question.attr("data-question-type", newType);
      let typeLabels = EnglishLineTest.questionTypeLabels;
      $question
        .find(".form-question-type")
        .text(typeLabels[newType] || newType);
      let $configContainer = $question.find(".form-question-config");
      $configContainer.empty();

      return $question;
    },

    /**
     * Actualiza los datos de una pregunta
     *
     * @param {number} sectionIndex - Índice de la sección
     * @param {number} questionIndex - Índice de la pregunta
     * @param {string} field - Campo a actualizar
     * @param {*} value - Nuevo valor
     */

    updateSectionData: function (sectionIndex, field, value) {
        if (this.sectionsData[sectionIndex]) { 
            this.sectionsData[sectionIndex][field] = value; 
            $(document).trigger("englishline_form_data_changed", [this.sectionsData]); 
        } else {
            console.error("Sección no encontrada con índice:", sectionIndex);
        }
    },

    updateQuestionData: function (sectionIndex, questionIndex, field, value) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (question) {

        if (field === "required" || field === "case_sensitive") {
          question[field] = !!value;
        } else if (field === "correct_answer") {
          question[field] = value === "true";
        } else if (field === "image_id") {
          question[field] = value ? parseInt(value, 10) || value : "";
        } else {
          question[field] = value;
        }

        if (field === "text") {
          let $section = this.$sectionsContainer.find(
            '.form-section[data-section-index="' + sectionIndex + '"]'
          );
          let $question = $section.find(
            '.form-question[data-question-index="' + questionIndex + '"]'
          );
          $question.find(".form-question-text").text(value || "Sin texto");
        }

        $(document).trigger("englishline_form_data_changed", [
          this.sectionsData,
        ]);
      }
    },

    /**
     * Guarda el formulario
     */
    saveForm: function () {
      let self = this;


    let imageCount = 0;
    
    for (let i = 0; i < this.sectionsData.length; i++) {
        let section = this.sectionsData[i];
        if (section.questions) {
            for (let j = 0; j < section.questions.length; j++) {
                let q = section.questions[j];
                if (q.type === 'image') {
                    imageCount++;
                    q.image_id = q.image_id ? parseInt(q.image_id, 10) || q.image_id : '';
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
        alert(
          "Las siguientes secciones no tienen preguntas: " +
            emptySections.join(", ")
        );
        return;
      }

      this.$saveFormBtn.prop("disabled", true).text("Guardando...");

      if (typeof window.englishline_test === "undefined") {
        alert(
          "Error: No se puede guardar el formulario debido a un problema en la configuración. Por favor, contacta al administrador."
        );
        this.$saveFormBtn.prop("disabled", false).text("Guardar Examen");
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
          form_id: this.formId,
          form_data: JSON.stringify(this.sectionsData),
        },
        success: function (response) {
          if (response.success) {
            if (self.formId === 0 && response.data && response.data.form_id) {
              self.formId = response.data.form_id;
              self.$formBuilder.data("form-id", self.formId);

              if (history.pushState) {
                let newUrl = window.location.href.replace(
                  "page=englishline-test-forms&action=new",
                  "page=englishline-test-forms&action=edit&form_id=" +
                    self.formId
                );
                window.history.pushState({ path: newUrl }, "", newUrl);
              }
            }

            alert("El examen se ha guardado correctamente.");
          } else {
            alert(
              "Error al guardar el examen: " +
                (response.data ? response.data.message : "Error desconocido")
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Error en la solicitud AJAX:", status, error);
          alert(
            "Error de conexión al guardar el examen. Por favor, inténtalo de nuevo."
          );
        },
        complete: function () {
          self.$saveFormBtn.prop("disabled", false).text("Guardar Examen");
        },
      });
    },

    updateOptionData: function (
      sectionIndex,
      questionIndex,
      optionIndex,
      field,
      value
    ) {
      let question = this.sectionsData[sectionIndex]?.questions[questionIndex];
      if (question && question.options && question.options[optionIndex]) {
        question.options[optionIndex][field] = value;
        $(document).trigger("englishline_form_data_changed", [
          this.sectionsData,
        ]);
      }
    },
  };
})(jQuery);

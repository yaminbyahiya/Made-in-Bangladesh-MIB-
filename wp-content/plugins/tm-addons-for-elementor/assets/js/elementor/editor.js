(
	function( $ ) {
		'use strict';

		function TMAddonsBuilder() {
			var self = this;

			self.init = function() {
				if ( elementor.panel ) {
					self.run();
				} else {
					// First open, the panel is not available yet.
					elementor.once( 'preview:loaded', self.run.bind( self ) );
				}
			}

			self.run = function() {
				if ( 'undefined' === typeof elementor.config.document.tm_builder ) {
					return;
				}

				self.addMenuConditions();

				self.addConditionField();
				self.removeConditionField();
				self.toggleField();

				self.saveConditions();
				self.checkConflicted();

				self.toggleModal();

				self.search();

				$( document.body ).on( 'appended', '.tm-condition-form__fields', function( e, $form, count ) {
					self.search();
				} );
			}

			self.search = function() {
				var $form = $( '.tm-condition-form' );

				$( '[data-name="id"]', $form ).each( function() {
					$( this ).find( 'select' ).select2( {
						placeholder: $tmAddonsConditionData.condition_id_placeholder,
						ajax: {
							url: $tmAddonsConditionData.ajax_url,
							dataType: 'json',
							data: function data( params ) {
								var $el        = $( this ),
								    $container = $el.closest( '.tm-condition-form__conditions' ),
								    postType   = $container.find( '.tm-condition-form__condition' ).filter( '[data-name="singular"]' ).find( 'select' ).val();

								return {
									q: params.term,
									post_type: postType,
									action: 'tm_condition_search'
								};
							},
							processResults: function( response ) {
								return {
									results: response
								};
							},
							cache: true
						},
						allowClear: true,
						minimumInputLength: 1
					} ).trigger( 'change' );
				} );
			}

			/**
			 * Add Conditions Button
			 */
			self.addMenuConditions = function() {
				var footerView = elementor.getPanelView().footer.currentView;

				footerView.ui.menuConditions = footerView.addSubMenuItem( 'saver-options', {
					before: 'save-template',
					name: 'tm_conditions',
					icon: 'eicon-flow',
					title: $tmAddonsConditionData.condition_button_text,
					callback: function() {
						self.openModal();
					}
				} );

				footerView.ui.menuConditions.toggle( ! ! elementor.config.document.tm_builder.settings.location );
			}

			self.openModal = function() {
				$( '.tm-modal-editor' ).fadeIn().addClass( 'open' );
			}

			self.closeModal = function() {
				$( '.tm-modal-editor' ).fadeOut().removeClass( 'open' );
			}

			self.toggleModal = function() {
				$( document.body ).on( 'click', '.tm-modal__close-button, .tm-modal__backdrop', function( e ) {
					e.preventDefault();

					self.closeModal();
				} );
			}

			// Condition Fields
			self.addConditionField = function() {
				var $form = $( '.tm-condition-form' );
				var conditionFieldTemplate = window.wp.template( 'tm-condition-field' );

				$form.on( 'click', '.tm-condition-form__add-new', function( e ) {
					e.preventDefault();

					var $button = $( this ),
					    $fields = $button.closest( '.tm-condition-form__content' ).children( '.tm-condition-form__fields' ),
					    name    = $button.data( 'name' ),
					    count   = $button.data( 'count' );

					$button.data( 'count', count + 1 );

					var data = {
						name: name,
						count: count
					};

					$fields.append( conditionFieldTemplate( data ) );

					$fields.trigger( 'appended', [ $form, count ] );
				} );
			}

			self.removeConditionField = function() {
				var $form = $( '.tm-condition-form' );

				$form.on( 'click', '.tm-condition-form__remove', function( e ) {
					var $button = $( this );

					$button.closest( '.tm-condition-form__field' ).hide().remove();
				} );
			}

			self.toggleField = function() {
				$( document.body ).on( 'change', '.tm-condition-form__field :input', function() {
					var $input     = $( this ),
					    $container = $input.closest( '.tm-condition-form__field' ),
					    optionName = $input.closest( '[data-name]' ).data( 'name' );

					var $dependencies = $( '[data-dependency]', $container ).filter( function() {
						var dependencies = $( this ).data( 'dependency' );

						return optionName in dependencies || optionName + '!' in dependencies;
					} );

					if ( ! $dependencies.length ) {
						return;
					}

					$dependencies.each( function() {
						var $field       = $( this ),
						    dependencies = $field.data( 'dependency' );

						var valid = true;

						_.each( dependencies, function( value, key ) {
							var keyParts   = key.match( /([a-z_\-0-9]+)(!?)$/ ),
							    pureKey    = keyParts[ 1 ],
							    isNegative = ! ! keyParts[ 2 ];

							var $dependencyInput = $( '[data-name="' + pureKey + '"] :input', $container );
							var instanceValue = $dependencyInput.is( ':checkbox' ) ? $dependencyInput.is( ':checked' ) : $dependencyInput.val();
							var isContain = value == instanceValue;

							if ( value instanceof Array && value.length ) {
								isContain = value.indexOf( instanceValue ) > - 1;
							}

							if ( (
								     isNegative && isContain
							     ) || (
								     ! isNegative && ! isContain
							     ) ) {
								valid = false;
							}
						} );

						$( ':input', $field ).val( '' ).trigger( 'change' );

						if ( ! valid ) {
							$field.addClass( 'hidden' );
						} else {
							$field.removeClass( 'hidden' );
						}
					} );

				} ).trigger( 'change' );
			}

			// Ajax Handler
			self.saveConditions = function() {
				var $form = $( '.tm-condition-form' );

				$form.on( 'submit', function( e ) {
					e.preventDefault();

					var formData = $( this ).serialize();

					elementorCommon.ajax.addRequest( 'tm_builder_save_conditions', {
						data: {
							conditions: formData,
						},
						success: function success( data ) {
							$( '.tm-modal__save-button' ).removeClass( 'loading' );

							self.closeModal();
						}
					} );
				} );

				$( document.body ).on( 'click', '.tm-modal__save-button', function( e ) {
					e.preventDefault();

					$( this ).addClass( 'loading' );
					$form.trigger( 'submit' );
				} );
			}

			self.checkConflicted = function() {
				$( document.body ).on( 'appended', '.tm-condition-form__fields', function( e, $form, count ) {
					var formData = $form.serialize();

					self.conflictRequest( formData, count );
				} );

				$( document.body ).on( 'change', '.tm-condition-form__field :input', function() {
					var $input     = $( this ),
					    $form      = $input.closest( '.tm-condition-form' ),
					    $container = $input.closest( '.tm-condition-form__field' ),
					    formData   = $form.serialize(),
					    index      = $container.data( 'index' );

					self.conflictRequest( formData, index );
				} );
			}

			self.conflictRequest = function( formData, index ) {
				return elementorCommon.ajax.addRequest( 'tm_builder_check_conditions_conflicts', {
					data: {
						conditions: formData,
						index: index
					},
					success: function success( data ) {
						$( '#tm-condition-form__field-' + index )
							.removeClass( 'minimnog-error' )
							.find( '.conditions-conflict-message' ).remove();

						if ( data ) {
							$( '#tm-condition-form__field-' + index )
								.addClass( 'minimnog-error' )
								.append( '<div class="conditions-conflict-message">' + data + '</div>' );
						}
					}
				} );
			}

			if ( 'object' === typeof elementor ) {
				self.init();
			} else {
				console.log( 'elementor is not defined' );
			}
		}

		new TMAddonsBuilder();
	}
)( jQuery );

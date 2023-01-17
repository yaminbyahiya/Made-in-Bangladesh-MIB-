(
	function( $ ) {

		'use strict';

		var EditorViews,
		    Editor;

		EditorViews = {

			LayoutView: null,
			HeaderView: null,
			HeaderInsertButton: null,
			HeaderSyncButton: null,
			LoadingView: null,
			BodyView: null,
			ErrorView: null,
			LibraryCollection: null,
			TabsCollection: null,
			CollectionView: null,
			TabsCollectionView: null,
			TemplateItemView: null,
			InsertTemplateBehavior: null,
			TabsItemView: null,
			TemplateModel: null,
			TabModel: null,
			PreviewView: null,
			HeaderBack: null,
			HeaderLogo: null,
			HeaderActions: null,

			init: function() {
				var self = this;

				self.TemplateModel = Backbone.Model.extend( {
					defaults: {
						template_id: 0,
						name: '',
						title: '',
						thumbnail: '',
						preview: '',
						source: '',
						type: '',
						tags: []
					}
				} );

				self.TabModel = Backbone.Model.extend( {
					defaults: {
						slug: '',
						title: ''
					}
				} );

				self.HeaderView = Marionette.LayoutView.extend( {
					id: 'tm-template-modal-header',
					template: '#tmpl-tm-template-modal-header',

					ui: {
						closeModal: '#tm-template-modal-header-close-modal'
					},

					events: {
						'click @ui.closeModal': 'onCloseModalClick'
					},

					regions: {
						headerLogo: '#tm-template-modal-header-logo-area',
						headerTabs: '#tm-template-modal-header-tabs',
						headerActions: '#tm-template-modal-header-actions',
						headerSync: '#tm-template-modal-header-sync'
					},

					onCloseModalClick: function() {
						Editor.closeModal();
					}
				} );

				self.LibraryCollection = Backbone.Collection.extend( {
					model: self.TemplateModel
				} );

				self.TabsCollection = Backbone.Collection.extend( {
					model: self.TabModel
				} );

				self.PreviewView = Marionette.ItemView.extend( {

					template: '#tmpl-tm-template-modal-preview',

					id: 'tm-templatate-item-preview-wrap',

					ui: {
						iframe: 'iframe'
					},

					onRender: function() {
						this.ui.iframe.attr( 'src', this.getOption( 'url' ) ).hide();

						var _this       = this,
						    loadingView = new self.LoadingView().render();

						this.$el.append( loadingView.el );
						this.ui.iframe.on( 'load', function() {
							_this.$el.find( '.elementor-loader-wrapper' ).remove();
							_this.ui.iframe.show();
						} );
					}
				} );

				self.HeaderBack = Marionette.ItemView.extend( {
					template: '#tmpl-tm-template-modal-header-back',

					id: 'tm-template-modal-header-back',

					ui: {
						button: 'button'
					},

					events: {
						'click @ui.button': 'onBackClick',
					},

					onBackClick: function() {
						Editor.setPreview( 'back' );
					}
				} );

				self.HeaderLogo = Marionette.ItemView.extend( {
					template: '#tmpl-tm-template-modal-header-logo',
					id: 'tm-template-modal-header-logo'
				} );

				self.BodyView = Marionette.LayoutView.extend( {
					id: 'tm-template-library-content',

					template: '#tmpl-tm-template-modal-content',

					regions: {
						contentTemplates: '.tm-templates-list',
					}
				} );

				self.InsertTemplateBehavior = Marionette.Behavior.extend( {
					ui: {
						insertButtons: [ '.tm-template-insert' ],
					},

					events: {
						'click @ui.insertButtons': 'onInsertButtonClick'
					},

					onInsertButtonClick: function( event ) {

						var templateModel = this.view.model,
						    options       = {};

						Editor.layout.showLoadingView();

						Editor.getTemplatesContent( templateModel.get( 'source' ), templateModel.get( 'template_id' ), {
							data: {
								with_page_settings: true
							},
							success: function( data ) {

								Editor.closeModal();

								if ( null !== Editor.atIndex ) {
									options.at = Editor.atIndex;
								}

								options.withPageSettings = true;

								$e.run( 'document/elements/import', {
									model: templateModel,
									data: data,
									options: options
								} );

								jQuery( "#elementor-panel-saver-button-save-options, #elementor-panel-saver-button-publish" ).removeClass( "elementor-disabled" );
								Editor.atIndex = null;

							},
							error: function( err ) {
								console.log( err );
							}
						} );
					}
				} );

				self.HeaderInsertButton = Marionette.ItemView.extend( {
					template: '#tmpl-tm-template-modal-insert-button',

					id: 'tm-template-modal-insert-button',

					behaviors: {
						insertTemplate: {
							behaviorClass: self.InsertTemplateBehavior
						}
					}
				} );

				self.HeaderSyncButton = Marionette.ItemView.extend( {
					template: '#tmpl-tm-template-modal-sync-button',

					id: 'tm-template-modal-sync-button',

					ui: { sync: '.tm-template-sync' },
					events: { 'click @ui.sync': 'onSyncClick' },

					onSyncClick: function() {
						var _this = this;
						_this.ui.sync.addClass( 'eicon-animation-spin' );
						Editor.requestLibraryData( {
							onUpdate: function() {
								_this.ui.sync.removeClass( 'eicon-animation-spin' );
							},
							forceUpdate: true,
							forceSync: true
						} );
					}
				} );

				self.TemplateItemView = Marionette.ItemView.extend( {

					template: '#tmpl-tm-template-modal-item',

					className: function() {

						var urlClass    = ' tm-template-has-url',
						    sourceClass = ' elementor-template-library-template-';

						if ( '' === this.model.get( 'preview' ) ) {
							urlClass = ' tm-template-no-url';
						}

						sourceClass += 'remote';

						return 'elementor-template-library-template' + sourceClass + urlClass;
					},

					ui: function() {
						return {
							previewButton: '.tm-template-library-template-preview',
						};
					},

					events: function() {
						return {
							'click @ui.previewButton': 'onPreviewButtonClick',
						};
					},

					onPreviewButtonClick: function() {

						if ( '' === this.model.get( 'url' ) ) {
							return;
						}

						Editor.setPreview( this.model );
					},

					behaviors: {
						insertTemplate: {
							behaviorClass: self.InsertTemplateBehavior
						}
					}
				} );

				self.TabsItemView = Marionette.ItemView.extend( {

					template: '#tmpl-tm-template-modal-tabs-item',

					className: function() {
						return 'tm-template-tabs-item';
					},

					ui: function() {
						return {
							item: '.elementor-template-library-menu-item'
						};
					},

					events: function() {
						return {
							'click @ui.item': 'onTabClick'
						};
					},

					onRender: function() {
						if ( this.model.get( 'slug' ) === Editor.getTab() ) {
							this.ui.item.addClass( 'elementor-active' );
						}
					},

					onTabClick: function( event ) {
						var $target = jQuery( event.target ),
						    val     = $target.data( 'value' ),
						    $parent = $target.closest( '.tm-template-tabs-item' );

						$target.addClass( 'elementor-active' );
						$parent.siblings().find( '.elementor-template-library-menu-item' ).removeClass( 'elementor-active' );

						Editor.setFilter( 'type', val );
						Editor.setTab( val );
					}

				} );

				self.TabsCollectionView = Marionette.CompositeView.extend( {

					id: 'tm-template-modal-tabs',

					template: '#tmpl-tm-template-modal-tabs',

					childViewContainer: '#tm-modal-tabs-items',

					getChildView: function( childModel ) {
						return self.TabsItemView;
					}

				} );

				self.CollectionView = Marionette.CompositeView.extend( {

					template: '#tmpl-tm-template-modal-templates',

					id: 'tm-template-library-templates',

					childViewContainer: '#tm-modal-templates-container',

					initialize: function() {
						this.listenTo( Editor.channels.templates, 'filter:change', this._renderChildren );
					},

					filter: function( childModel ) {
						var type = Editor.getFilter( 'type' );

						if ( ! type ) {
							return true;
						}

						return _.contains( childModel.get( 'type' ), type );
					},

					getChildView: function( childModel ) {
						return self.TemplateItemView;
					},

					setMasonrySkin: function() {
						// Run masonry here

						// var masonry = new elementorModules.utils.Masonry({
						// 	container: this.$childViewContainer,
						// 	items: this.$childViewContainer.children()
						// });

						// this.$childViewContainer.imagesLoaded(masonry.run.bind(masonry));
					},

					onRenderCollection: function() {
						this.setMasonrySkin();
					}
				} );

				self.LayoutView = Marionette.LayoutView.extend( {
					el: '#tm-template-modal',
					regions: {
						modalHeader: '.dialog-header',
						modalContent: '.dialog-message'
					},

					initialize: function() {
						this.getRegion( 'modalHeader' ).show( new self.HeaderView() );
						this.listenTo( Editor.channels.back, 'getback:change', this.getBack );
						this.listenTo( Editor.channels.layout, 'preview:change', this.switchPreview );
					},

					getBack: function() {
						this.showLoadingView();
						Editor.getTemplates();
					},

					switchPreview: function() {
						var _self = this;
						var header  = this.getHeaderView(),
						    preview = Editor.getPreview();

						if ( 'back' === preview ) {
							header.headerLogo.show( new self.HeaderLogo() );
							header.headerTabs.show( new self.TabsCollectionView( {
								collection: Editor.collections.tabs
							} ) );
							header.headerActions.empty();
							header.headerSync.show( new self.HeaderSyncButton() );
							Editor.getBack();
							Editor.setTab( Editor.getTab() );

							return;
						}

						if ( 'initial' === preview ) {
							header.headerLogo.show( new self.HeaderLogo() );
							header.headerActions.empty();
							header.headerSync.show( new self.HeaderSyncButton() );

							return;
						}

						this.getRegion( 'modalContent' ).show( new self.PreviewView( {
							'preview': preview.get( 'preview' ),
							'url': preview.get( 'url' )
						} ) );

						header.headerLogo.empty();
						header.headerTabs.show( new self.HeaderBack() );
						header.headerActions.show( new self.HeaderInsertButton( {
							model: preview
						} ) );
						header.headerSync.empty();
					},

					getHeaderView: function() {
						return this.getRegion( 'modalHeader' ).currentView;
					},

					getContentView: function() {
						return this.getRegion( 'modalContent' ).currentView;
					},

					showLoadingView: function() {
						this.modalContent.show( new self.LoadingView() );
					},

					showTemplatesView: function( templatesCollection ) {
						this.getRegion( 'modalContent' ).show( new self.BodyView() );

						var contentView = this.getContentView(),
						    headerView  = this.getHeaderView();

						Editor.collections.tabs = new self.TabsCollection( Editor.getTabs() );

						contentView.contentTemplates.show( new self.CollectionView( {
							collection: templatesCollection
						} ) );

						headerView.headerTabs.show( new self.TabsCollectionView( {
							collection: Editor.collections.tabs
						} ) );
					},
				} );

				self.LoadingView = Marionette.ItemView.extend( {
					id: 'tm-template-modal-loading',
					template: '#tmpl-tm-template-modal-loading'
				} );

				self.ErrorView = Marionette.ItemView.extend( {
					id: 'tm-template-modal-loading',
					template: '#tmpl-tm-template-modal-error'
				} );
			}
		};

		Editor = {
			modal: false,
			layout: false,
			channels: {},
			collections: {},
			tabs: {},
			defaultTab: '',
			atIndex: null,
			templatesCollection: null,
			typesCollection: null,

			init: function() {
				window.elementor.on(
					'document:loaded',
					window._.bind( Editor.onPreviewLoaded, Editor )
				);

				EditorViews.init();
			},

			onPreviewLoaded: function() {

				this.initAddTemplateButton();

				window.elementor.$previewContents.on(
					'click.addTemplate',
					'.tm-add-section-btn',
					_.bind( this.openModal, this )
				);

				this.channels = {
					templates: Backbone.Radio.channel( 'TM_EDITOR:templates' ),
					tabs: Backbone.Radio.channel( 'TM_EDITOR:tabs' ),
					layout: Backbone.Radio.channel( 'TM_EDITOR:layout' ),
					back: Backbone.Radio.channel( 'TM_EDITOR:back' )
				};

				this.tabs = $tmAddonsTemplateData.tabs;
				this.defaultTab = $tmAddonsTemplateData.defaultTab;
			},

			initAddTemplateButton: function() {
				var $icon = $tmAddonsTemplateData.icon,
				    text  = $tmAddonsTemplateData.add_template_text;

				var $addNewSection = window.elementor.$previewContents.find( '.elementor-add-new-section' ),
				    addTemplate    = "<div class='elementor-add-section-area-button tm-add-section-btn' title='" + text + "' style='background: #000;'><img src='" + $icon + "' alt=''></div>";

				if ( $addNewSection.length ) {
					$( addTemplate ).prependTo( $addNewSection );
				}

				window.elementor.$previewContents.on(
					'click.addTemplate',
					'.elementor-editor-section-settings .elementor-editor-element-add',
					function() {

						var $this    = $( this ),
						    $section = $this.closest( '.elementor-top-section' ),
						    modelID  = $section.data( 'model-cid' );

						if ( elementor.previewView.collection.length ) {
							$.each( elementor.previewView.collection.models, function( index, model ) {
								if ( modelID === model.cid ) {
									Editor.atIndex = index;
								}
							} );
						}

						setTimeout( function() {
							var $addNew = $section.prev( '.elementor-add-section' ).find( '.elementor-add-new-section' );
							$addNew.prepend( addTemplate );
						}, 100 );
					}
				);
			},

			getFilter: function( name ) {
				return this.channels.templates.request( 'filter:' + name );
			},

			setFilter: function( name, value ) {
				this.channels.templates.reply( 'filter:' + name, value );
				this.channels.templates.trigger( 'filter:change' );
			},

			getPreview: function() {
				return this.channels.layout.request( 'preview' );
			},

			setPreview: function( value, silent ) {
				this.channels.layout.reply( 'preview', value );

				if ( ! silent ) {
					this.channels.layout.trigger( 'preview:change' );
				}
			},

			getTab: function() {
				return this.channels.tabs.request( 'filter:tabs' );
			},

			setTab: function( value, silent ) {

				this.channels.tabs.reply( 'filter:tabs', value );

				if ( ! silent ) {
					this.channels.tabs.trigger( 'filter:change' );
				}

			},

			getTabs: function() {

				var tabs = [];

				_.each( this.tabs, function( item, slug ) {
					tabs.push( {
						slug: slug,
						title: item.title
					} );
				} );

				return tabs;
			},

			getBack: function() {
				this.channels.back.trigger( 'getback:change' );
			},

			getTemplates: function() {
				var self = this;

				if ( self.templatesCollection ) {
					this.layout.showTemplatesView( self.templatesCollection );
				} else {
					this.requestLibraryData( {
						onBeforeUpdate: null,
						onUpdate: function() {

						},
					} );
				}
			},

			requestLibraryData: function( options ) {
				var self = this;

				if ( ! self.templatesCollection || options.forceUpdate ) {
					if ( options.onBeforeUpdate ) {
						options.onBeforeUpdate();
					}

					var data = {
						source: 'tm-library'
					};

					if ( options.forceSync ) {
						data.sync = true;
					}

					return elementorCommon.ajax.addRequest( 'tm_get_library_data', {
						data: data,
						success: function( response ) {
							self.templatesCollection = new EditorViews.LibraryCollection( response.templates );
							self.layout.showTemplatesView( self.templatesCollection );

							if ( options.onUpdate ) {
								options.onUpdate();
							}
						}
					} );
				} else {
					if ( options.onUpdate ) {
						options.onUpdate();
					}
				}
			},

			getTemplatesContent: function( source, id, ajaxOptions ) {
				var options = {
					data: {
						source: source,
						template_id: id
					}
				};

				if ( ajaxOptions ) {
					jQuery.extend( true, options, ajaxOptions );
				}

				return elementorCommon.ajax.addRequest( 'tm_get_template_data', options );
			},

			openModal: function() {
				this.getModal().show();

				if ( ! this.layout ) {
					this.layout = new EditorViews.LayoutView();
					this.layout.showLoadingView();
				}

				this.setTab( this.defaultTab, true );
				this.getBack();
				this.getTemplates();
				this.setPreview( 'initial' );
			},

			closeModal: function() {
				this.getModal().hide();
			},

			getModal: function() {
				if ( ! this.modal ) {
					this.modal = elementor.dialogsManager.createWidget( 'lightbox', {
						id: 'tm-template-modal',
						className: 'elementor-templates-modal',
						closeButton: false
					} );
				}

				return this.modal;
			}
		};

		$( window ).on( 'elementor:init', Editor.init );

	}
)( jQuery );

/**
 * Internal dependencies
 */
// import HelpTab from './help';
// import ProSettings from './pro-extension';
// import RecommendedTab from './recomended';
// import StarterTab from './starter';
// import Sidebar from './sidebar';
// import CustomizerLinks from './customizer';
// import Notices from './notices';
import map from 'lodash/map';
import LazyLoad from 'react-lazy-load';
/**
 * WordPress dependencies
 */
const { __, sprintf } = wp.i18n;
const { Fragment, Component, render, PureComponent } = wp.element;
const { Modal, Spinner, ButtonGroup, Dropdown, Icon, Button, ExternalLink, ToolbarGroup, ToggleControl, MenuItem, Tooltip } = wp.components;

class NoorSingleTemplateImport extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			colorPalette: this.props.colorPalette ? this.props.colorPalette : '',
			fontPair: this.props.fontPair ? this.props.fontPair : '',
			palettes: ( noorStarterParams.palettes ? noorStarterParams.palettes : [] ),
			fonts: ( noorStarterParams.fonts ? noorStarterParams.fonts : [] ),
			overrideColors: false,
			overrideFonts: false,
			isOpenCheckColor: false,
			isOpenCheckFont: false,
		};
	}
	capitalizeFirstLetter( string ) {
		return string.charAt( 0 ).toUpperCase() + string.slice( 1 );
	}
	render() {
		const item = this.props.item;
		let pluginsActive = true;
		let pluginsPremium = false;
		return (
			<div className="nst-grid-single-site">
				<div className="nst-import-selection-item">
					<div className="nst-import-selection">
						<img src={ item.pages[this.state.selectedPage].image.replace( '-scaled', '' ) } alt={ item.pages[this.state.selectedPage].title } />
					</div>
				</div>
				<div className="nst-import-selection-options">
					<div className="nst-import-single-selection-options-wrap">
						<div className="nst-import-selection-title">
							<h2 className="align-center">{ __( 'Template:', 'noor-starter-templates' ) } <span>{ item.name }</span><br></br> { __( 'Selected Page:', 'noor-starter-templates' ) } <span>{ item.pages[this.state.selectedPage].title }</span></h2>
						</div>
						<div className="nst-import-grid-title">
							<h2>{ __( 'Page Template Plugins', 'noor-starter-templates' ) }</h2>
						</div>
							<ul className="noor-required-wrap">
								{ map( item.plugins, ( slug ) => {
									if ( noorStarterParams.plugins[ slug ] ) {
										return (
											<li className="plugin-required">
												{ noorStarterParams.plugins[ slug ].title }  <span class="plugin-status">{ ( 'notactive' === noorStarterParams.plugins[ slug ].state ? __( 'Not Installed', 'noor-starter-templates' ) : noorStarterParams.plugins[ slug ].state ) }</span> { ( 'active' !== noorStarterParams.plugins[ slug ].state && 'thirdparty' === noorStarterParams.plugins[ slug ].src ? <span class="plugin-install-required">{ __( 'Please install and activate this third-party premium plugin' ) }</span> : '' ) }
											</li>
										);
									}
								} ) }
							</ul>
							<p className="desc-small note-about-colors">{ __( '*Single Page templates will follow your website current global colors and typography settings, you can import without effecting your current site. Or you can optionally override your websites global colors and typography by enabling the settings below.', 'noor-starter-templates' ) }</p>
							<ToggleControl
								label={ __( 'Override Your Sites Global Colors?', 'noor-starter-templates' ) }
								checked={ ( undefined !== this.state.overrideColors ? this.state.overrideColors : false ) }
								onChange={ value => ( this.state.overrideColors ? this.setState( { overrideColors: false } ) : this.setState( { isOpenCheckColor: true } ) ) }
							/>
							{ this.state.isOpenCheckColor ?
								<Modal
									className="nsp-confirm-modal"
									title={ __( 'Override Your Sites Colors on Import?', 'noor-starter-templates' ) }
									onRequestClose={ () => {
										this.setState( { isOpenCheckColor: false } )
									} }>
									<p className="desc-small note-about-colors">{ __( 'This will override the customizer settings for global colors on your current site when you import this page template.', 'noor-starter-templates' ) }</p>
									<div className="nsp-override-model-buttons">
										<Button className="nsp-cancel-override" onClick={ () => {
											this.setState( { isOpenCheckColor: false, overrideColors: false } );
										} }>
											{ __( 'Cancel', 'noor-starter-templates' ) }
										</Button>
										<Button className="nsp-do-override" isPrimary onClick={ () => {
											this.setState( { isOpenCheckColor: false, overrideColors: true } );
										} }>
											{ __( 'Override Colors', 'noor-starter-templates' ) }
										</Button>
									</div>
								</Modal>
								: null }
							{ this.state.overrideColors && this.state.colorPalette && (
								<Fragment>
									<h3>{ __( 'Selected Color Palette', 'noor-starter-templates' ) }</h3>
									{ map( this.state.palettes, ( { palette, colors } ) => {
										if ( palette !== this.state.colorPalette ) {
											return;
										}
										return (
											<div className="nst-palette-btn nst-selected-color-palette">
												{ map( colors, ( color, index ) => {
													return (
														<div key={ index } style={ {
															width: 22,
															height: 22,
															marginBottom: 0,
															marginRight:'3px',
															transform: 'scale(1)',
															transition: '100ms transform ease',
														} } className="noor-swatche-item-wrap">
															<span
																className={ 'noor-swatch-item' }
																style={ {
																	height: '100%',
																	display: 'block',
																	width: '100%',
																	border: '1px solid rgb(218, 218, 218)',
																	borderRadius: '50%',
																	color: `${ color }`,
																	boxShadow: `inset 0 0 0 ${ 30 / 2 }px`,
																	transition: '100ms box-shadow ease',
																} }
																>
															</span>
														</div>
													)
												} ) }
											</div>
										)
									} ) }
								</Fragment>
							) }
							<ToggleControl
								label={ __( 'Override Your Sites Fonts?', 'noor-starter-templates' ) }
								checked={ ( undefined !== this.state.overrideFonts ? this.state.overrideFonts : false ) }
								onChange={ value => ( this.state.overrideFonts ? this.setState( { overrideFonts: false } ) : this.setState( { isOpenCheckFont: true } ) ) }
							/>
							{ this.state.isOpenCheckFont ?
								<Modal
									className="nsp-confirm-modal"
									title={ __( 'Override Your Sites Fonts on Import?', 'noor-starter-templates' ) }
									onRequestClose={ () => {
										this.setState( { isOpenCheckFont: false } )
									} }>
									<p className="desc-small note-about-colors">{ __( 'This will override the customizer typography settings on your current site when you import this page template.', 'noor-starter-templates' ) }</p>
									<div className="nsp-override-model-buttons">
										<Button className="nsp-cancel-override" onClick={ () => {
											this.setState( { isOpenCheckFont: false, overrideFonts: false } );
										} }>
											{ __( 'Cancel', 'noor-starter-templates' ) }
										</Button>
										<Button className="nsp-do-override" isPrimary onClick={ () => {
											this.setState( { isOpenCheckFont: false, overrideFonts: true } );
										} }>
											{ __( 'Override Colors', 'noor-starter-templates' ) }
										</Button>
									</div>
								</Modal>
							: null }
							{ this.state.fontPair && this.state.overrideFonts && (
								<Fragment>
									<h3 className="nst-selected-font-pair-title">{ __( 'Selected Font Pair', 'noor-starter-templates' ) }</h3>
									{ map( this.state.fonts, ( { font, img, name } ) => {
										if ( font !== this.state.fontPair ) {
											return;
										}
										return (
											<div className="nst-selected-font-pair">
												<img src={ img } className="font-pairing" />
												<h4>{ name }</h4>
											</div>
										)
									} ) }
								</Fragment>
							) }
							{ this.state.progress === 'plugins' && (
								<div class="noor_starter_templates_response">{ noorStarterParams.plugin_progress }</div>
							) }
							{ this.state.progress === 'content' && (
								<div class="noor_starter_templates_response">{ noorStarterParams.content_progress }</div>
							) }
							{ this.state.progress === 'contentNew' && (
								<div class="noor_starter_templates_response">{ noorStarterParams.content_new_progress }</div>
							) }
							{ this.state.isFetching && (
								<Spinner />
							) }
							{ ! noorStarterParams.isNoor && (
								<div class="noor_starter_templates_response">
									<h2>{ __( 'This Template Requires the Noor Theme', 'noor-starter-templates' ) }</h2>
									<ExternalLink href={ 'https://link.pixeldima.com/noor-home' }>{ __( 'Get Noor Theme', 'noor-starter-templates' ) }</ExternalLink>
								</div>
							) }
							{ noorStarterParams.isNoor && (
								<Fragment>
									<Button className="dima-defaults-save" isPrimary disabled={ this.state.isFetching } onClick={ () => {
											this.props.onChange( { isImporting: true, overrideColors: this.state.overrideColors, overrideFonts: this.state.overrideFonts } );
											this.props.runInstallSingle( item.slug, this.props.selectedPage );
										} }>
											{ __( 'Start Importing Page', 'noor-starter-templates' ) }
									</Button>
								</Fragment>
							) }
					</div>
				</div>
			</div>
		);
	}
}
export default NoorSingleTemplateImport;
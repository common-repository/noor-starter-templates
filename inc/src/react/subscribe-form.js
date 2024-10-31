/**
 * Internal dependencies
 */
/**
 * WordPress dependencies
 */
const { __, sprintf } = wp.i18n;
const { Fragment, Component, render, PureComponent } = wp.element;
const { Modal, Spinner, ButtonGroup, Dropdown, Icon, Button, ExternalLink, ToolbarGroup, CheckboxControl, TextControl, ToggleControl, MenuItem, Tooltip, PanelBody } = wp.components;
import {
	arrowLeft,
	download,
	update,
	chevronLeft,
	chevronDown,
} from '@wordpress/icons';

class NoorSubscribeForm extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			email: noorStarterParams.user_email,
			privacy: false,
			privacyError: false,
		};
	}
	render() {
		return (
			<div className={ 'dima-subscribe-form-box' }>
				<h2>{ __( 'Subscribe and Import', 'noor-starter-templates' ) }</h2>
				<p>{ __( "Subscribe to learn about new starter templates and features for Noor.", 'noor-starter-templates' ) }</p>
				<TextControl
					type="text"
					className={ 'dima-subscribe-email-input' }
					label={ __( 'Email:', 'noor-starter-templates' )  }
					value={ this.state.email }
					placeholder={ __( 'example@example.com', 'noor-starter-templates' ) }
					onChange={ value => this.setState( { email: value } ) }
				/>
				{ this.props.emailError && (
					<span className="dima-subscribe-form-error">{ __( 'Invalid Email, Please enter a valid email.', 'noor-starter-templates' ) }</span>
				) }
				<CheckboxControl
					label={ <Fragment>{ __( 'Accept', 'noor-starter-templates' ) } <ExternalLink href={ 'https://www.pixeldima.com/privacy-policy/' }>{ __( 'Privacy Policy', 'noor-starter-templates' ) }</ExternalLink></Fragment> }
					help={ __( 'We do not spam, unsubscribe anytime.', 'noor-starter-templates' ) }
					checked={ this.state.privacy }
					onChange={ value => this.setState( { privacy: value } ) }
				/>
				{ this.state.privacyError && (
					<span className="dima-subscribe-form-error">{ __( 'Please Accept Privacy Policy', 'noor-starter-templates' ) }</span>
				) }
				<Button className="dima-defaults-save" isPrimary onClick={ () => {
						if ( this.state.privacy ) {
							this.setState( { privacyError: false } );
							this.props.onRun( this.state.email );
						} else {
							this.setState( { privacyError: true } );
						}
					} }>
						{ __( 'Subscribe and Start Importing' ) }
				</Button>
			</div>
		);
	}
}
export default NoorSubscribeForm;
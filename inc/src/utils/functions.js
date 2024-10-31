import { __ } from '@wordpress/i18n';

export const noorTryParseJSON = (jsonString) => {
	try {
	  const o = JSON.parse(jsonString);
	  if (o && typeof o === "object") {
		return o;
	  }
	} catch (e) {}

	return false;
}

export const sendPostMessage = ( data ) => {
	const frame = document.getElementById( 'noor-starter-preview' );
	if ( ! frame ) {
		return;
	}

	frame.contentWindow.postMessage(
		data,
		'*'
	);
};
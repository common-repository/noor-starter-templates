/**
 * Internal dependencies
 */

import map from 'lodash/map';
import LazyLoad from 'react-lazy-load';
import NoorImporterFullPreview from './full-preview-mode.js';
import NoorSubscribeForm from './subscribe-form';
import { noorTryParseJSON } from '../utils/functions';
import ICONS from '../icons';
import { NOOR_PALETTES } from './colors';
import axios from 'axios';

/**
 * WordPress dependencies
 */
import Skeleton from '@mui/material/Skeleton';
import Box from '@mui/material/Box';
import Grid from '@mui/material/Unstable_Grid2';

const { __, sprintf } = wp.i18n;
const { Fragment, Component, render } = wp.element;
const {
  Modal,
  Spinner,
  Dropdown,
  Icon,
  Button,
  ExternalLink,
  ToggleControl,
  MenuItem,
  Tooltip,
  PanelBody
} = wp.components;
import { update, chevronLeft } from '@wordpress/icons';

class NoorImporter extends Component {
  constructor() {
    super(...arguments);
    this.runAjax = this.runAjax.bind(this);
    this.runPluginInstall = this.runPluginInstall.bind(this);
    this.runPluginInstallSingle = this.runPluginInstallSingle.bind(this);
    this.runSubscribe = this.runSubscribe.bind(this);
    this.runSubscribeSingle = this.runSubscribeSingle.bind(this);
    this.loadTemplateData = this.loadTemplateData.bind(this);
    this.reloadTemplateData = this.reloadTemplateData.bind(this);
    this.loadPluginData = this.loadPluginData.bind(this);
    this.focusMode = this.focusMode.bind(this);
    this.fullFocusMode = this.fullFocusMode.bind(this);
    this.jumpToImport = this.jumpToImport.bind(this);
    this.selectedMode = this.selectedMode.bind(this);
    this.selectedFullMode = this.selectedFullMode.bind(this);
    this.backToDash = this.backToDash.bind(this);
    this.saveConfig = this.saveConfig.bind(this);
    this.state = {
      category: 'all',
      plugins: 'all',
      level: 'all',
      activeTemplate: '',
      colorPalette: '',
      fontPair: '',
      search: null,
      isFetching: false,
      isImporting: false,
      isSelected: false,
      response: '',
      isPageSelected: false,
      starterSettings: noorStarterParams.starterSettings
        ? JSON.parse(noorStarterParams.starterSettings)
        : {},
      selectedPage: 'home',
      progress: '',
      focusMode: false,
      finished: false,
      overrideColors: false,
      overrideFonts: false,
      isOpenCheckColor: false,
      isOpenCheckFont: false,
      isOpenCheckPast: false,
      removePast: false,
      errorTemplates: false,
      templates: noorStarterParams.templates ? noorStarterParams.templates : [],
      etemplates: noorStarterParams.etemplates ? noorStarterParams.etemplates : [],
      activeTemplates: false,
      fonts: noorStarterParams.fonts ? noorStarterParams.fonts : [],
      logo: noorStarterParams.logo ? noorStarterParams.logo : '',
      hasContent: noorStarterParams.has_content ? noorStarterParams.has_content : false,
      hasPastContent: noorStarterParams.has_previous ? noorStarterParams.has_previous : false,
      isSaving: false,
      isLoadingPlugins: false,
      activePlugins: false,
      showForm: true,
      templatePlugins: '',
      isSubscribed: noorStarterParams.subscribed ? true : false,
      email: noorStarterParams.user_email,
      privacy: false,
      emailError: false,
      privacyError: false,
      settingOpen: false,
      installContent: true,
      installCustomizer: true,
      installWidgets: true
    };
  }

  /**
   * This method will return if the user chose the page builder or not.
   */
  componentDidMount() {
    if (localStorage.getItem('_builderType')) {
      wp.api.loadPromise.then(() => {
        this.saveConfig('builderType', localStorage.getItem('_builderType'));
      });
    }
  }

  saveConfig(setting, settingValue) {
    this.setState({ isSaving: true });

    if (setting === 'builderType') {
      localStorage.setItem('_builderType', settingValue);
    }

    const config = noorStarterParams.starterSettings
      ? JSON.parse(noorStarterParams.starterSettings)
      : {};
    if (!config[setting]) {
      config[setting] = '';
    }
    config[setting] = settingValue;
    this.setState({ starterSettings: config });
    if (wp.api.models.Settings) {
      const settingModel = new wp.api.models.Settings({
        noor_starter_templates_config: JSON.stringify(config)
      });
      settingModel.save().then((response) => {
        this.setState({ starterSettings: config, isSaving: false });
        noorStarterParams.starterSettings = JSON.stringify(config);
      });
    }
  }

  capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }

  focusMode(template_id) {
    this.setState({
      activeTemplate: template_id,
      focusMode: true,
      isSelected: false,
      activePlugins: false
    });
  }

  fullFocusMode(template_id) {
    this.setState({
      activeTemplate: template_id,
      focusMode: true,
      isSelected: true,
      activePlugins: false
    });
  }

  jumpToImport(template_id) {
    this.setState({
      isImporting: true,
      activeTemplate: template_id,
      focusMode: true,
      isSelected: true,
      fontPair: '',
      colorPalette: '',
      activePlugins: false
    });
  }
  selectedFullMode() {
    this.setState({ isSelected: true });
  }
  selectedMode(page_id) {
    this.setState({
      selectedPage: page_id,
      isPageSelected: true,
      isImporting: true
    });
  }
  backToDash() {
    this.setState({
      isFetching: false,
      activeTemplate: '',
      activePlugins: false,
      overrideColors: false,
      overrideFonts: false,
      colorPalette: '',
      fontPair: '',
      focusMode: false,
      finished: false,
      isImporting: false,
      isSelected: false,
      isPageSelected: false,
      progress: '',
      selectedPage: 'home'
    });
  }

  reloadTemplateData() {
    this.setState({
      errorTemplates: false,
      isSaving: true,
      activeTemplates: 'Reloading...'
    });
    var data_key = '';
    var data_email = '';

    var data = new FormData();
    data.append('action', 'noor_import_reload_template_data');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('api_key', data_key);
    data.append('api_email', data_email);
    data.append('template_type', localStorage.getItem('_builderType'));
    var control = this;
    this.fetchTemplates(data, control);
  }

  fetchTemplates(data, control) {
    axios({
      method: 'POST',
      url: noorStarterParams.ajax_url,
      data: data,
      headers: {
        cache: false,
        'Content-Type': false,
        processData: false,
        'Access-Control-Allow-Origin': '*',
        crossDomain: true
      },
      withCredentials: true
    })
      .then(function (response) {
        if (response) {

          const o = noorTryParseJSON(response.data);
          if (o) {
            control.setState({
              activeTemplates: o,
              errorTemplates: false,
              isSaving: false
            });
          } else {
            control.setState({
              activeTemplates: 'error',
              errorTemplates: true,
              isSaving: false
            });
          }
        }
      })
      .catch(function (error) {
        control.setState({
          activeTemplates: 'error',
          errorTemplates: true,
          isSaving: false
        });
      });
  }

  loadTemplateData() {
    this.setState({
      errorTemplates: false,
      isSaving: true,
      activeTemplates: 'loading'
    });
    var data_key = '';
    var data_email = '';

    var data = new FormData();
    data.append('action', 'noor_import_get_template_data');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('api_key', data_key);
    data.append('api_email', data_email);
    data.append('template_type', localStorage.getItem('_builderType'));
    var control = this;

    this.fetchTemplates(data, control);
  }

  loadPluginData(selected, builder) {
    this.setState({ isLoadingPlugins: true });
    var data = new FormData();
    data.append('action', 'noor_check_plugin_data');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('selected', selected);
    data.append('builder', builder);
    var control = this;
    axios({
      method: 'POST',
      url: noorStarterParams.ajax_url,
      data: data,
      headers: {
        cache: false,
        'Content-Type': false,
        processData: false,
        'Access-Control-Allow-Origin': '*',
        crossDomain: true
      },
      withCredentials: true
    })
      .then(function (response) {
        if (response) {
          if (200 !== response.status) {
            control.setState({
              templatePlugins: 'error',
              activePlugins: true,
              isLoadingPlugins: false
            });
          } else {
            if (typeof response === 'object' && response !== null) {
              control.setState({
                templatePlugins: response.data,
                activePlugins: true,
                isLoadingPlugins: false
              });
            } else {
              control.setState({
                templatePlugins: 'error',
                activePlugins: true,
                isLoadingPlugins: false
              });
            }
          }
        }
      })
      .catch(function (error) {
        control.setState({
          templatePlugins: 'error',
          activePlugins: true,
          isLoadingPlugins: false
        });
      });
  }

  runPluginInstallSingle(selected, page_id, builder) {
    this.setState({ progress: 'plugins', isFetching: true, showForm: false });
    var data = new FormData();
    data.append('action', 'noor_import_install_plugins');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('selected', selected);
    data.append('builder', builder);
    data.append('page_id', page_id);
    this.runPageAjax(data);
  }

  runSubscribeSingle(email, selected) {
    this.setState({ progress: 'subscribe', isFetching: true, showForm: false });
    var data = new FormData();
    data.append('action', 'noor_import_subscribe');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('email', email);
    data.append('selected', selected);
    this.runPageAjax(data);
  }

  runRemovePast(selected, builder) {
    this.setState({ progress: 'remove', isFetching: true, showForm: false });
    var data = new FormData();
    data.append('action', 'noor_remove_past_import_data');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('selected', selected);
    data.append('builder', builder);
    this.runAjax(data);
  }

  runPluginInstall(selected, builder) {
    this.setState({ progress: 'plugins', isFetching: true, showForm: false });
    var data = new FormData();
    data.append('action', 'noor_import_install_plugins');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('selected', selected);
    data.append('builder', builder);
    this.runAjax(data);
  }

  runSubscribe(email, selected) {
    this.setState({ progress: 'subscribe', isFetching: true, showForm: false });
    var data = new FormData();
    data.append('action', 'noor_import_subscribe');
    data.append('security', noorStarterParams.ajax_nonce);
    data.append('email', email);
    data.append('selected', selected);
    this.runAjax(data);
  }

  runPageAjax(data) {
    var control = this;
    axios({
      method: 'POST',
      url: noorStarterParams.ajax_url,
      data: data,
      headers: {
        cache: false,
        'Content-Type': false,
        processData: false,
        'Access-Control-Allow-Origin': '*',
        crossDomain: true
      },
      withCredentials: true
    })
      .then(function (response) {
        if ('undefined' !== typeof response.data.status && 'newAJAX' === response.data.status) {
          control.state.progress = 'contentNew';
          control.runPageAjax(data);
        } else if (
          'undefined' !== typeof response.data.status &&
          'subscribeSuccess' === response.data.status
        ) {
          control.setState({ progress: 'plugins' });
          var newData = new FormData();
          newData.append('action', 'noor_import_install_plugins');
          newData.append('security', noorStarterParams.ajax_nonce);
          newData.append('selected', control.state.activeTemplate);
          newData.append('builder', control.state.starterSettings['builderType']);
          newData.append('page_id', control.state.selectedPage);
          control.runPageAjax(newData);
        } else if (
          'undefined' !== typeof response.data.status &&
          'pluginSuccess' === response.data.status
        ) {
          control.setState({ progress: 'content' });
          var newData = new FormData();
          newData.append('action', 'noor_import_single_data');
          newData.append('security', noorStarterParams.ajax_nonce);
          newData.append('selected', control.state.activeTemplate);
          newData.append('builder', control.state.starterSettings['builderType']);
          newData.append('page_id', control.state.selectedPage);
          newData.append('override_colors', control.state.overrideColors);
          newData.append('override_fonts', control.state.overrideFonts);
          newData.append('palette', control.state.colorPalette);
          newData.append('font', control.state.fontPair);
          control.runPageAjax(newData);
        } else if ('undefined' !== typeof response.data.message) {
          control.setState({
            finished: true,
            hasContent: true,
            hasPastContent: true,
            isFetching: false,
            colorPalette: '',
            fontPair: '',
            focusMode: false,
            isImporting: false,
            isSelected: false,
            progress: '',
            showForm: true,
            response: '<p>' + response.data.message + '</p>'
          });
        } else if (
          response.data === 'emailDomainPostError' ||
          response.data === 'emailDomainPreError'
        ) {
          control.setState({
            isFetching: false,
            progress: '',
            showForm: true,
            emailError: true
          });
        } else {
          control.setState({
            finished: true,
            hasContent: true,
            hasPastContent: true,
            isFetching: false,
            colorPalette: '',
            fontPair: '',
            focusMode: false,
            isImporting: false,
            isSelected: false,
            progress: '',
            showForm: true,
            response:
              '<div class="notice noor_starter_templates_response notice-error"><p>' +
              response.data +
              '</p></div>'
          });
        }
      })
      .catch(function (error) {
        control.setState({
          finished: true,
          hasContent: true,
          hasPastContent: true,
          isFetching: false,
          colorPalette: '',
          fontPair: '',
          focusMode: false,
          isImporting: false,
          isSelected: false,
          progress: '',
          showForm: true,
          response:
            '<div class="notice noor_starter_templates_response notice-error"><p>Error: ++' +
            error.statusText +
            ' (' +
            error.status +
            ')' +
            '</p></div>'
        });
      });
  }

  runAjax(data) {
    var control = this;
    axios({
      method: 'POST',
      url: noorStarterParams.ajax_url,
      data: data,
      headers: {
        cache: false,
        'Content-Type': false,
        processData: false,
        'Access-Control-Allow-Origin': '*',
        crossDomain: true
      },
      withCredentials: true
    })
      .then(function (response) {
        const responseData = response.data;
        if ('undefined' !== typeof responseData.status && 'newAJAX' === responseData.status) {
          if (control.state.progress === 'contentNew') {
            control.state.progress = 'contentNewer';
          } else if (control.state.progress === 'contentNewer') {
            control.state.progress = 'contentNewest';
          } else {
            control.state.progress = 'contentNew';
          }
          control.runAjax(data);
        } else if (
          'undefined' !== typeof responseData.status &&
          'customizerAJAX' === responseData.status
        ) {
          var newData = new FormData();
          newData.append('security', noorStarterParams.ajax_nonce);
          if (control.state.installCustomizer) {
            control.setState({ progress: 'customizer' });
            newData.append('action', 'noor_import_customizer_data');
            newData.append('wp_customize', 'on');
          } else {
            control.setState({ progress: 'widgets' });
            newData.append('action', 'noor_after_import_data');
          }
          control.runAjax(newData);
        } else if (
          'undefined' !== typeof responseData.status &&
          'afterAllImportAJAX' === responseData.status
        ) {
          control.setState({ progress: 'widgets' });
          var newData = new FormData();
          newData.append('action', 'noor_after_import_data');
          newData.append('security', noorStarterParams.ajax_nonce);
          control.runAjax(newData);
        } else if (
          'undefined' !== typeof responseData.status &&
          'pluginSuccess' === responseData.status
        ) {
          var newData = new FormData();
          newData.append('security', noorStarterParams.ajax_nonce);
          if (control.state.installContent) {
            control.setState({ progress: 'content' });
            newData.append('action', 'noor_import_demo_data');
            newData.append('builder', control.state.starterSettings['builderType']);
            newData.append('selected', control.state.activeTemplate);
            newData.append('palette', control.state.colorPalette);
            newData.append('font', control.state.fontPair);
          } else if (control.state.installCustomizer) {
            control.setState({ progress: 'customizer' });
            newData.append('action', 'noor_import_customizer_data');
            newData.append('builder', control.state.starterSettings['builderType']);
            newData.append('selected', control.state.activeTemplate);
            newData.append('palette', control.state.colorPalette);
            newData.append('font', control.state.fontPair);
            newData.append('wp_customize', 'on');
          } else if (control.state.installWidgets) {
            control.setState({ progress: 'widgets' });
            newData.append('builder', control.state.starterSettings['builderType']);
            newData.append('selected', control.state.activeTemplate);
            newData.append('palette', control.state.colorPalette);
            newData.append('font', control.state.fontPair);
            newData.append('action', 'noor_after_import_data');
          } else {
            newData.append('action', 'noor_after_import_data');
          }
          control.runAjax(newData);
        } else if (
          'undefined' !== typeof responseData.status &&
          'removeSuccess' === responseData.status
        ) {
          control.setState({ progress: 'plugins' });
          var newData = new FormData();
          newData.append('action', 'noor_import_install_plugins');
          newData.append('security', noorStarterParams.ajax_nonce);
          newData.append('selected', control.state.activeTemplate);
          newData.append('builder', control.state.starterSettings['builderType']);
          control.runAjax(newData);
        } else if (
          'undefined' !== typeof responseData.status &&
          'subscribeSuccess' === responseData.status
        ) {
          var newData = new FormData();
          if (control.state.removePast) {
            this.setState({ progress: 'remove' });
            newData.append('action', 'noor_remove_past_import_data');
          } else {
            control.setState({ progress: 'plugins' });
            newData.append('action', 'noor_import_install_plugins');
          }
          newData.append('security', noorStarterParams.ajax_nonce);
          newData.append('selected', control.state.activeTemplate);
          newData.append('builder', control.state.starterSettings['builderType']);
          control.runAjax(newData);
        } else if ('undefined' !== typeof responseData.message) {
          control.setState({
            finished: true,
            hasContent: true,
            hasPastContent: true,
            isFetching: false,
            colorPalette: '',
            fontPair: '',
            focusMode: false,
            isImporting: false,
            isSelected: false,
            isPageSelected: false,
            progress: '',
            showForm: true,
            response: '<p>' + responseData.message + '</p>'
          });
        } else if ('undefined' !== typeof responseData.success && !responseData.success) {
          control.setState({
            finished: true,
            hasContent: true,
            hasPastContent: true,
            isFetching: false,
            colorPalette: '',
            fontPair: '',
            focusMode: false,
            isImporting: false,
            isSelected: false,
            isPageSelected: false,
            progress: '',
            showForm: true,
            response:
              '<div class="notice noor_starter_templates_response notice-error"><p>' +
              __(
                'Failed Import. Something went wrong internally. Please try again.',
                'noor-starter-templates'
              ) +
              '</p></div>'
          });
        } else if (
          responseData === 'emailDomainPostError' ||
          responseData === 'emailDomainPreError'
        ) {
          control.setState({
            isFetching: false,
            progress: '',
            showForm: true,
            emailError: true
          });
        } else {
          control.setState({
            finished: true,
            hasContent: true,
            hasPastContent: true,
            isFetching: false,
            colorPalette: '',
            fontPair: '',
            focusMode: false,
            isImporting: false,
            isSelected: false,
            isPageSelected: false,
            progress: '',
            showForm: true,
            response:
              '<div class="notice noor_starter_templates_response notice-error"><p>' +
              responseData +
              '</p></div>'
          });
        }
      })
      .catch(function (error) {
        control.setState({
          finished: true,
          hasContent: true,
          hasPastContent: true,
          isFetching: false,
          colorPalette: '',
          fontPair: '',
          focusMode: false,
          isImporting: false,
          isSelected: false,
          isPageSelected: false,
          progress: '',
          showForm: true,
          response:
            '<div class="notice noor_starter_templates_response notice-error"><p>Error-: ' +
            error.statusText +
            ' (' +
            error.status +
            ')' +
            '</p></div>'
        });
      });
  }
  render() {
    let builderTypeName = __('Gutenberg', 'noor-starter-templates');
    let builderTypeIcon = ICONS.gbIcon;
    if (this.state.starterSettings['builderType'] === 'elementor') {
      builderTypeName = __('Elementor', 'noor-starter-templates');
      builderTypeIcon = ICONS.eIcon;
    } else if (this.state.starterSettings['builderType'] === 'wpbakery') {
      builderTypeName = __('WPBakery', 'noor-starter-templates');
      builderTypeIcon = ICONS.vcIcon;
    }
    if (this.state.starterSettings['builderType'] === 'custom') {
      builderTypeName = noorStarterParams.custom_name
        ? noorStarterParams.custom_name
        : __('Pro Designs', 'noor-starter-templates');
      builderTypeIcon = noorStarterParams.custom_icon ? (
        <img
          className="components-menu-items__item-icon custom-image-icon-src"
          src={noorStarterParams.custom_icon}
        />
      ) : (
        ICONS.cIcon
      );
    }

    const errorMessageShow =
      this.state.isSaving || false === this.state.activeTemplates || this.state.errorTemplates
        ? true
        : false;

    const NoorImportSingleMode = () => {
      const item = this.state.activeTemplates[this.state.activeTemplate];
      let pluginsBundle = false;
      let pluginsPremium = false;
      return (
        <div className="nst-grid-single-site">
          <div className="nst-import-selection-item">
            <div className="nst-import-selection">
              <img
                src={item.pages[this.state.selectedPage].image}
                alt={item.pages[this.state.selectedPage].title}
              />
            </div>
          </div>
          <div className="nst-import-selection-options">
            <div className="nst-import-single-selection-options-wrap">
              <div className="nst-import-selection-title">
                <h2>
                  {__('Template:', 'noor-starter-templates')} <span>{item.name}</span>
                  <br></br> {__('Selected Page:', 'noor-starter-templates')}{' '}
                  <span>{item.pages[this.state.selectedPage].title}</span>
                </h2>
              </div>
              <PanelBody
                title={__('Advanced Settings', 'noor-blocks')}
                initialOpen={this.state.settingOpen}
                onToggle={(value) =>
                  this.state.settingOpen
                    ? this.setState({ settingOpen: false })
                    : this.setState({ settingOpen: true })
                }>
                <div className="nst-import-grid-title">
                  <h2>{__('Page Template Plugins', 'noor-starter-templates')}</h2>
                </div>
                {this.state.isLoadingPlugins && <Spinner />}
                {!this.state.activePlugins && !this.state.isLoadingPlugins && (
                  <Fragment>
                    {this.loadPluginData(item._ID, this.state.starterSettings['builderType'])}
                  </Fragment>
                )}
                {this.state.activePlugins && (
                  <Fragment>
                    {this.state.templatePlugins && 'error' !== this.state.templatePlugins && (
                      <ul className="noor-required-wrap">
                        {map(this.state.templatePlugins, ({ state, src, title }) => {
                          if ('active' !== state && 'bundle' === src) {
                            pluginsBundle = true;
                          }
                          if ('active' !== state && ('thirdparty' === src || 'unknown' === src)) {
                            pluginsPremium = true;
                          }
                          return (
                            <li
                              className={`plugin-required${
                                'active' !== state && 'bundle' === src
                                  ? ' bundle-install-required'
                                  : ''
                              }`}>
                              {title}{' '}
                              <span class="plugin-status">
                                {'notactive' === state
                                  ? __('Not Installed', 'noor-starter-templates')
                                  : state}
                              </span>{' '}
                              {'active' !== state && 'thirdparty' === src ? (
                                <span class="plugin-install-required">
                                  {__(
                                    'Please install and activate this third-party premium plugin'
                                  )}
                                </span>
                              ) : (
                                ''
                              )}
                            </li>
                          );
                        })}
                      </ul>
                    )}
                    {this.state.templatePlugins && 'error' === this.state.templatePlugins && (
                      <Fragment>
                        <p className="desc-small install-third-party-notice">
                          {__(
                            'Error accessing active plugin information, you may import but first manually check that you have installed all required plugins.',
                            'noor-starter-templates'
                          )}
                        </p>
                        <ul className="noor-required-wrap">
                          {map(item.plugins, (slug) => {
                            if (noorStarterParams.plugins[slug]) {
                              if (
                                'active' !== noorStarterParams.plugins[slug].state &&
                                'bundle' === noorStarterParams.plugins[slug].src
                              ) {
                                pluginsBundle = true;
                              }
                              return (
                                <li
                                  className={`plugin-required${
                                    'active' !== noorStarterParams.plugins[slug].state &&
                                    'bundle' === noorStarterParams.plugins[slug].src
                                      ? ' bundle-install-required'
                                      : ''
                                  }`}>
                                  {noorStarterParams.plugins[slug].title}{' '}
                                  <span class="plugin-status">
                                    {'notactive' === noorStarterParams.plugins[slug].state
                                      ? __('Not Installed', 'noor-starter-templates')
                                      : noorStarterParams.plugins[slug].state}
                                  </span>{' '}
                                  {'active' !== noorStarterParams.plugins[slug].state &&
                                  'thirdparty' === noorStarterParams.plugins[slug].src ? (
                                    <span class="plugin-install-required">
                                      {__(
                                        'Please install and activate this third-party premium Plugin'
                                      )}
                                    </span>
                                  ) : (
                                    ''
                                  )}
                                </li>
                              );
                            } else {
                              return (
                                <li className={`plugin-required`}>
                                  {slug}{' '}
                                  <span class="plugin-status">
                                    {__('Unknown', 'noor-starter-templates')}
                                  </span>
                                </li>
                              );
                            }
                          })}
                        </ul>
                      </Fragment>
                    )}
                  </Fragment>
                )}
                <p className="desc-small note-about-colors">
                  {__(
                    '*Single Page templates will follow your website current global colors and typography settings, you can import without effecting your current site. Or you can optionally override your websites global colors and typography by enabling the settings below.',
                    'noor-starter-templates'
                  )}
                </p>
                <ToggleControl
                  label={__('Override Your Sites Global Colors?', 'noor-starter-templates')}
                  checked={
                    undefined !== this.state.overrideColors ? this.state.overrideColors : false
                  }
                  onChange={(value) =>
                    this.state.overrideColors
                      ? this.setState({ overrideColors: false })
                      : this.setState({ isOpenCheckColor: true })
                  }
                />
                {this.state.isOpenCheckColor ? (
                  <Modal
                    className="nsp-confirm-modal"
                    title={__('Override Your Sites Colors on Import?', 'noor-starter-templates')}
                    onRequestClose={() => {
                      this.setState({ isOpenCheckColor: false });
                    }}>
                    <p className="desc-small note-about-colors">
                      {__(
                        'This will override the customizer settings for global colors on your current site when you import this page template.',
                        'noor-starter-templates'
                      )}
                    </p>
                    <div className="nsp-override-model-buttons">
                      <Button
                        className="nsp-cancel-override"
                        onClick={() => {
                          this.setState({
                            isOpenCheckColor: false,
                            overrideColors: false
                          });
                        }}>
                        {__('Cancel', 'noor-starter-templates')}
                      </Button>
                      <Button
                        className="nsp-do-override"
                        isPrimary
                        onClick={() => {
                          this.setState({
                            isOpenCheckColor: false,
                            overrideColors: true
                          });
                        }}>
                        {__('Override Colors', 'noor-starter-templates')}
                      </Button>
                    </div>
                  </Modal>
                ) : null}
                {this.state.overrideColors && this.state.colorPalette && (
                  <Fragment>
                    <h3>{__('Selected Color Palette', 'noor-starter-templates')}</h3>
                    {map(NOOR_PALETTES, ({ palette, colors }) => {
                      if (palette !== this.state.colorPalette) {
                        return;
                      }
                      return (
                        <div className="nst-palette-btn nst-selected-color-palette">
                          {map(colors, (color, index) => {
                            return (
                              <div
                                key={index}
                                style={{
                                  width: 22,
                                  height: 22,
                                  marginBottom: 0,
                                  marginRight: '3px',
                                  transform: 'scale(1)',
                                  transition: '100ms transform ease'
                                }}
                                className="noor-swatche-item-wrap">
                                <span
                                  className={'noor-swatch-item'}
                                  style={{
                                    height: '100%',
                                    display: 'block',
                                    width: '100%',
                                    border: '1px solid rgb(218, 218, 218)',
                                    borderRadius: '50%',
                                    color: `${color}`,
                                    boxShadow: `inset 0 0 0 ${30 / 2}px`,
                                    transition: '100ms box-shadow ease'
                                  }}></span>
                              </div>
                            );
                          })}
                        </div>
                      );
                    })}
                  </Fragment>
                )}
                <ToggleControl
                  label={__('Override Your Sites Fonts?', 'noor-starter-templates')}
                  checked={
                    undefined !== this.state.overrideFonts ? this.state.overrideFonts : false
                  }
                  onChange={(value) =>
                    this.state.overrideFonts
                      ? this.setState({ overrideFonts: false })
                      : this.setState({ isOpenCheckFont: true })
                  }
                />
                {this.state.isOpenCheckFont ? (
                  <Modal
                    className="nsp-confirm-modal"
                    title={__('Override Your Sites Fonts on Import?', 'noor-starter-templates')}
                    onRequestClose={() => {
                      this.setState({ isOpenCheckFont: false });
                    }}>
                    <p className="desc-small note-about-colors">
                      {__(
                        'This will override the customizer typography settings on your current site when you import this page template.',
                        'noor-starter-templates'
                      )}
                    </p>
                    <div className="nsp-override-model-buttons">
                      <Button
                        className="nsp-cancel-override"
                        onClick={() => {
                          this.setState({
                            isOpenCheckFont: false,
                            overrideFonts: false
                          });
                        }}>
                        {__('Cancel', 'noor-starter-templates')}
                      </Button>
                      <Button
                        className="nsp-do-override"
                        isPrimary
                        onClick={() => {
                          this.setState({
                            isOpenCheckFont: false,
                            overrideFonts: true
                          });
                        }}>
                        {__('Override Fonts', 'noor-starter-templates')}
                      </Button>
                    </div>
                  </Modal>
                ) : null}
                {this.state.fontPair && this.state.overrideFonts && (
                  <Fragment>
                    <h3 className="nst-selected-font-pair-title">
                      {__('Selected Font Pair', 'noor-starter-templates')}
                    </h3>
                    {map(this.state.fonts, ({ font, img, name }) => {
                      if (font !== this.state.fontPair) {
                        return;
                      }
                      return (
                        <div className="nst-selected-font-pair">
                          <img src={img} className="font-pairing" />
                          <h4>{name}</h4>
                        </div>
                      );
                    })}
                  </Fragment>
                )}
              </PanelBody>
              {this.state.progress === 'subscribe' && (
                <div class="noor_starter_templates_response">
                  <Spinner />
                  {noorStarterParams.subscribe_progress}
                </div>
              )}
              {this.state.progress === 'plugins' && (
                <div class="noor_starter_templates_response">
                  <Spinner />
                  {noorStarterParams.plugin_progress}
                </div>
              )}
              {this.state.progress === 'content' && (
                <div class="noor_starter_templates_response">
                  <Spinner />
                  {noorStarterParams.content_progress}
                </div>
              )}
              {this.state.progress === 'contentNew' && (
                <div class="noor_starter_templates_response">
                  <Spinner />
                  {noorStarterParams.content_new_progress}
                </div>
              )}
              {this.state.progress === 'contentNewer' && (
                <div class="noor_starter_templates_response">
                  <Spinner />
                  {noorStarterParams.content_newer_progress}
                </div>
              )}
              {this.state.progress === 'contentNewest' && (
                <div class="noor_starter_templates_response">
                  <Spinner />
                  {noorStarterParams.content_newest_progress}
                </div>
              )}
              {!noorStarterParams.isNoor && (
                <div class="flex flex-column align-center noor_starter_templates_response">
                  <h2>{__('This Template Requires the Noor Theme', 'noor-starter-templates')}</h2>
                  <ExternalLink href={'https://link.pixeldima.com/noor-home'}>
                    {__('Get Noor Theme', 'noor-starter-templates')}
                  </ExternalLink>
                </div>
              )}
              {noorStarterParams.isNoor && (
                <Fragment>
                  {pluginsBundle && (
                    <div class="flex flex-column align-center noor_starter_templates_response">
                      <h2>
                        {__(
                          'Install Missing/Inactive Highlighted Premium plugins to Import.',
                          'noor-starter-templates'
                        )}
                      </h2>
                      <Button
                        component="a"
                        target="_blank"
                        isPrimary
                        href={'https://link.pixeldima.com/noor-home'}>
                        {__('Get Noor Theme', 'noor-starter-templates')}
                      </Button>
                    </div>
                  )}
                  {!pluginsBundle && (
                    <Fragment>
                      {this.state.showForm && !this.state.isSubscribed && (
                        <Fragment>
                          <NoorSubscribeForm
                            emailError={this.state.emailError}
                            onRun={(email) =>
                              this.runSubscribeSingle(email, item.name + '_' + item._ID)
                            }
                          />
                          <Button
                            className="dima-skip-start subscribe"
                            isPrimary
                            disabled={this.state.isFetching}
                            onClick={() => {
                              this.runPluginInstallSingle(
                                item._ID,
                                this.state.selectedPage,
                                this.state.starterSettings['builderType']
                              );
                            }}>
                            {__('Skip, start importing page', 'noor-starter-templates')}
                          </Button>
                        </Fragment>
                      )}
                      {this.state.showForm && this.state.isSubscribed && (
                        <Fragment>
                          <Button
                            className="dima-defaults-save"
                            isPrimary
                            disabled={this.state.isFetching}
                            onClick={() => {
                              this.runPluginInstallSingle(
                                item._ID,
                                this.state.selectedPage,
                                this.state.starterSettings['builderType']
                              );
                            }}>
                            {__('Start Importing Page', 'noor-starter-templates')}
                          </Button>
                        </Fragment>
                      )}
                    </Fragment>
                  )}
                </Fragment>
              )}
            </div>
          </div>
        </div>
      );
    };

    const NoorSiteMode = () => {
      const item = this.state.activeTemplates[this.state.activeTemplate];

      return (
        <Fragment>
          <div className="nst-import-selection-options">
            <div className="nst-import-grid-title">
              <h2>{__('Page Templates', 'noor-starter-templates')}</h2>
            </div>
            <div className="templates-grid">
              {map(item.pages, ({ title, id, image }) => {
                return (
                  <div className="nst-template-item">
                    <Button
                      key={id}
                      className="nst-import-btn"
                      isSmall
                      onClick={() => this.selectedMode(id)}>
                      <LazyLoad offsetBottom={200}>
                        <img src={image} alt={title} />
                      </LazyLoad>
                      <div className="demo-title">
                        <h4>
                          {title} <span>{__('View Details', 'noor-starter-templates')}</span>
                        </h4>
                      </div>
                    </Button>
                  </div>
                );
              })}
            </div>
            <div className="nst-import-selection-bottom">
              <Button
                className="dima-import-fullsite"
                isPrimary
                onClick={() => this.selectedFullMode()}>
                {__('Import Full Site', 'noor-starter-templates')}
              </Button>
            </div>
          </div>
        </Fragment>
      );
    };

    const NoorSitesGrid = () => {
      const control = this;
      const cats = ['all'];
      const _plugins = ['all'];
      if (this.state.activeTemplates && Object.keys(this.state.activeTemplates).length > 1) {
        {
          Object.keys(this.state.activeTemplates).map(function (key, index) {
            if (control.state.activeTemplates[key].categories) {
              for (let c = 0; c < control.state.activeTemplates[key].categories.length; c++) {
                if (!cats.includes(control.state.activeTemplates[key].categories[c])) {
                  cats.push(control.state.activeTemplates[key].categories[c]);
                }
              }
            }
            if (control.state.activeTemplates[key].plugins) {
              for (let c = 0; c < control.state.activeTemplates[key].plugins.length; c++) {
                if (!_plugins.includes(control.state.activeTemplates[key].plugins[c])) {
                  _plugins.push(control.state.activeTemplates[key].plugins[c]);
                }
              }
            }
          });
        }
      }
      const catControlsRender = cats.map((item) => {
        return (
          <MenuItem
            className={item === control.state.category ? 'active-item' : ''}
            isSelected={item === control.state.category ? true : false}
            onClick={() => this.setState({ category: item })}>
            {control.capitalizeFirstLetter(item)}
          </MenuItem>
        );
      });
      const pluginControlsRender = _plugins.map((item) => {
        return (
          <MenuItem
            className={item === control.state.plugins ? 'active-item' : ''}
            isSelected={item === control.state.plugins ? true : false}
            onClick={() => this.setState({ plugins: item })}>
            {noorStarterParams.plugins[item] &&
              control.capitalizeFirstLetter(noorStarterParams.plugins[item].title)}
            {!noorStarterParams.plugins[item] && __('All', 'noor-blocks')}
          </MenuItem>
        );
      });
      return (
        <div className="noor-site-grid-wrap">
          <div className="noor-site-header">
            <div className="noor-site-header-left">
              {cats.length > 1 && (
                <Fragment>
                  <Dropdown
                    className="noor-site-grid-header-popover"
                    contentClassName="nst-category-popover"
                    position="bottom center"
                    label={__('Select a Category', 'noor-blocks')}
                    renderToggle={({ isOpen, onToggle }) => (
                      <Button onClick={onToggle} aria-expanded={isOpen} icon={ICONS.tag}>
                        {control.state.category === 'all' && __('Select Category', 'noor-blocks')}
                        {control.state.category !== 'all' &&
                          this.capitalizeFirstLetter(control.state.category)}
                        <span>
                          <Icon className="nst-chev" icon={ICONS.angleDown} />
                        </span>
                      </Button>
                    )}
                    renderContent={({ isOpen, onToggle }) => <div>{catControlsRender}</div>}
                  />
                </Fragment>
              )}
              <Fragment>
                <Dropdown
                  className="noor-site-grid-header-popover"
                  contentClassName="nst-plugins-popover"
                  position="bottom center"
                  label={__('Select a Plugin', 'noor-blocks')}
                  renderToggle={({ isOpen, onToggle }) => (
                    <Button onClick={onToggle} aria-expanded={isOpen} icon={ICONS.puzzle}>
                      {control.state.plugins === 'all' && __('Select Plugin', 'noor-blocks')}
                      {control.state.plugins !== 'all' && (
                        <>
                          {noorStarterParams.plugins[control.state.plugins] &&
                            control.capitalizeFirstLetter(
                              noorStarterParams.plugins[control.state.plugins].title
                            )}
                          {!noorStarterParams.plugins[control.state.plugins] &&
                            this.capitalizeFirstLetter(control.state.plugins)}
                        </>
                      )}
                      <span>
                        <Icon className="nst-chev" icon={ICONS.angleDown} />
                      </span>
                    </Button>
                  )}
                  renderContent={({ isOpen, onToggle }) => <div>{pluginControlsRender}</div>}
                />
              </Fragment>
            </div>
          </div>
          <div className="templates-grid">
            {/* { map( ( this.state.starterSettings['builderType'] === 'elementor' ? this.state.etemplates : this.state.templates ), ( { name, key, slug, image, content, categories, keywords, pro, pages } ) => { */}
            {Object.keys(this.state.activeTemplates).map(function (key, index) {
              const _ID = control.state.activeTemplates[key]._ID;
              const name = control.state.activeTemplates[key].name;
              const image = control.state.activeTemplates[key].image;
              const categories = control.state.activeTemplates[key].categories;
              const plugins = control.state.activeTemplates[key].plugins;
              const keywords = control.state.activeTemplates[key].keywords;
              const pro = control.state.activeTemplates[key].pro === 'true';
              const pages = control.state.activeTemplates[key].pages;

              if (
                ('all' === control.state.category || categories.includes(control.state.category)) &&
                ('all' === control.state.plugins || plugins.includes(control.state.plugins)) &&
                (!control.state.search ||
                  (keywords &&
                    keywords.some((x) =>
                      x.toLowerCase().includes(control.state.search.toLowerCase())
                    )))
              ) {
                return (
                  <div className="nst-template-item">
                    <Button
                      key={key}
                      className="nst-import-btn"
                      isSmall
                      onClick={() =>
                        'custom' === control.state.starterSettings['builderType']
                          ? control.jumpToImport(_ID)
                          : control.fullFocusMode(_ID)
                      }>
                      <LazyLoad offsetBottom={700}>
                        <img
                          src={
                            pages && pages.home && pages.home.thumbnail
                              ? pages.home.thumbnail
                              : image
                          }
                          alt={name}
                        />
                      </LazyLoad>
                      <div className="demo-title">
                        <h4>{name}</h4>
                      </div>
                    </Button>
                    {undefined !== pro && pro && (
                      <Fragment>
                        <span className="dima-pro-template">{__('Pro', 'noor-starter-sites')}</span>
                      </Fragment>
                    )}
                  </div>
                );
              }
            })}
          </div>
        </div>
      );
    };

    const NoorFinishedPage = () => {
      const item = this.state.activeTemplates[this.state.activeTemplate];
      return (
        <div className="nst-grid-single-site">
          <div className="nst-import-selection-item">
            <div className="nst-import-selection">
              <img
                src={item.pages[this.state.selectedPage].image}
                alt={item.pages[this.state.selectedPage].title}
              />
            </div>
          </div>
          <div className="nst-import-selection-options">
            <div className="nst-import-single-selection-options-wrap">
              <div className="nst-import-selection-title">
                <h2>
                  {__('Template:', 'noor-starter-templates')} <span>{item.name}</span>
                  <br></br> {__('Selected Page:', 'noor-starter-templates')}{' '}
                  <span>{item.pages[this.state.selectedPage].title}</span>
                </h2>
              </div>
              <div className="nst-import-grid-title">
                <h2 className="align-center green-500">
                  {__('Import complete!', 'noor-starter-templates')}
                </h2>
                <div class="noor_starter_templates_finished">
                  <div dangerouslySetInnerHTML={{ __html: this.state.response }} />
                </div>
              </div>
            </div>
          </div>
        </div>
      );
    };

    const NoorFinished = () => {
      const item = this.state.activeTemplates[this.state.activeTemplate];
      return (
        <div className="nst-grid-single-site">
          <div className="nst-import-selection-item">
            <div className="nst-import-selection">
              <img
                src={
                  item.pages && item.pages['home'] && item.pages['home'].image
                    ? item.pages['home'].image
                    : item.image
                }
              />
            </div>
          </div>
          <div className="nst-import-selection-options">
            <div className="nst-import-single-selection-options-wrap">
              <div className="nst-import-selection-title">
                <h2>
                  {__('Template:', 'noor-starter-templates')} <span>{item.name}</span>
                </h2>
              </div>
              <div className="nst-import-grid-title">
                <h2 className="align-center green-500">
                  {__('Import complete!', 'noor-starter-templates')}
                </h2>
                <div class="noor_starter_templates_finished">
                  <div dangerouslySetInnerHTML={{ __html: this.state.response }} />
                </div>
              </div>
            </div>
          </div>
        </div>
      );
    };

    const ChooseBuilder = () => (
      <div
        className={`nst-choose-builder-wrap${
          noorStarterParams.ctemplates ? ' adjust-to-three-column' : ''
        }`}>
        <div className="nst-choose-builder-center">
          <h2 className="nst-choose-builder-title">
            {__('Choose Page Builder', 'noor-starter-templates')}
          </h2>
          <div className="nst-choose-builder-inner">
            {noorStarterParams.ctemplates && (
              <Button
                icon={
                  noorStarterParams.custom_icon ? (
                    <img className="custom-image-icon-src" src={noorStarterParams.custom_icon} />
                  ) : (
                    ICONS.cIcon
                  )
                }
                className="dima-import-select-type"
                onClick={() => {
                  this.saveConfig('builderType', 'custom');
                }}>
                {noorStarterParams.custom_name
                  ? noorStarterParams.custom_name
                  : __('Pro Designs', 'noor-starter-templates')}
              </Button>
            )}
            <Button
              icon={ICONS.gbIcon}
              className="dima-import-select-type"
              onClick={() => {
                this.saveConfig('builderType', 'blocks');
              }}>
              {__('Gutenberg', 'noor-starter-templates')}
            </Button>
            <Button
              icon={ICONS.eIcon}
              className="dima-import-select-type"
              onClick={() => {
                this.saveConfig('builderType', 'elementor');
              }}>
              {__('Elementor', 'noor-starter-templates')}
            </Button>
            <Button
              icon={ICONS.vcIcon}
              className="dima-import-select-type"
              onClick={() => {
                this.saveConfig('builderType', 'wpbakery');
              }}>
              {__('WPBakery', 'noor-starter-templates')}
            </Button>
          </div>
          {this.state.isSaving && (
            <div className="nst-overlay-saving">
              <Spinner />
            </div>
          )}
        </div>
      </div>
    );

    const NoorImportMode = () => {
      const item = this.state.activeTemplates[this.state.activeTemplate];
      let pluginsPremium = false;
      let pluginsBundle = false;
      return (
        <Fragment>
          <div className="nst-grid-single-site">
            <div className="nst-import-selection-item">
              <div className="nst-import-selection">
                <img
                  src={
                    item.pages && item.pages['home'] && item.pages['home'].image
                      ? item.pages['home'].image
                      : item.image
                  }
                  alt={item.name}
                />
              </div>
            </div>
            <div className="nst-import-selection-options">
              <div className="nst-import-selection-title">
                <div className="nst-import-single-selection-options-wrap">
                  <h2>
                    {__('Template:', 'noor-starter-templates')} <span>{item.name}</span>
                  </h2>
                </div>
              </div>
            </div>
          </div>
          <Modal
            className="nst-import-modal"
            title={__('Import Starter Template')}
            onRequestClose={() =>
              this.state.isFetching
                ? false
                : this.setState({
                    activeTemplate: '',
                    activePlugins: false,
                    colorPalette: '',
                    focusMode: false,
                    isImporting: false,
                    progress: ''
                  })
            }>
            {!noorStarterParams.isNoor && (
              <div class="flex flex-column noor_starter_templates_response">
                <h2>
                  {__('This Starter Template Requires the Noor Theme', 'noor-starter-templates')}
                </h2>
                <Button
                  component="a"
                  target="_blank"
                  isPrimary
                  href={'https://link.pixeldima.com/noor-home'}>
                  {__('Get Noor Theme', 'noor-starter-templates')}
                </Button>
              </div>
            )}
            {noorStarterParams.isNoor && (
              <Fragment>
                {!this.state.isFetching && (
                  <Fragment>
                    {this.state.hasContent && (
                      <div className="noor_starter_templates_notice info">
                        {this.state.hasPastContent ? (
                          <Fragment>{noorStarterParams.notice_previous}</Fragment>
                        ) : (
                          <Fragment>{noorStarterParams.notice}</Fragment>
                        )}
                      </div>
                    )}
                    {this.state.hasPastContent && (
                      <Fragment>
                        <ToggleControl
                          label={__(
                            'Delete Previously Imported Posts and Images?',
                            'noor-starter-templates'
                          )}
                          checked={
                            undefined !== this.state.removePast ? this.state.removePast : false
                          }
                          onChange={(value) =>
                            this.state.removePast
                              ? this.setState({ removePast: false })
                              : this.setState({ removePast: true })
                          }
                        />
                      </Fragment>
                    )}
                  </Fragment>
                )}
                <PanelBody title={__('Import Details', 'noor-blocks')} initialOpen={false}>
                  <div className="required-plugins-list">
                    <h3 className="required-plugins-list-header">
                      {__('Required Plugins', 'noor-starter-templates')}
                    </h3>
                    {this.state.isLoadingPlugins && <Spinner />}
                    {!this.state.activePlugins && !this.state.isLoadingPlugins && (
                      <Fragment>
                        {this.loadPluginData(item._ID, this.state.starterSettings['builderType'])}
                      </Fragment>
                    )}
                    {this.state.activePlugins && (
                      <Fragment>
                        {this.state.templatePlugins && 'error' !== this.state.templatePlugins && (
                          <ul className="noor-required-wrap">
                            {map(this.state.templatePlugins, ({ state, src, title }) => {
                              if ('active' !== state && 'bundle' === src) {
                                pluginsBundle = true;
                              }
                              if ('active' !== state && 'thirdparty' === src) {
                                pluginsPremium = true;
                              }
                              return (
                                <li
                                  className={`plugin-required${
                                    'active' !== state && 'bundle' === src
                                      ? ' bundle-install-required'
                                      : ''
                                  }`}>
                                  {title}{' '}
                                  <span class="plugin-status">
                                    {'notactive' === state
                                      ? __('Not Installed', 'noor-starter-templates')
                                      : state}
                                  </span>{' '}
                                  {'active' !== state && 'thirdparty' === src ? (
                                    <span class="plugin-install-required">
                                      {__(
                                        'Please install and activate this third-party premium plugin'
                                      )}
                                    </span>
                                  ) : (
                                    ''
                                  )}
                                </li>
                              );
                            })}
                          </ul>
                        )}
                        {this.state.templatePlugins && 'error' === this.state.templatePlugins && (
                          <Fragment>
                            <p className="desc-small install-third-party-notice">
                              {__(
                                '*Error accessing active plugin information, you may import but first manually check that you have installed all required plugins.',
                                'noor-starter-templates'
                              )}
                            </p>
                            <ul className="noor-required-wrap">
                              {map(item.plugins, (slug) => {
                                if (noorStarterParams.plugins[slug]) {
                                  if (
                                    'active' !== noorStarterParams.plugins[slug].state &&
                                    'bundle' === noorStarterParams.plugins[slug].src
                                  ) {
                                    pluginsBundle = true;
                                  }
                                  return (
                                    <li
                                      className={`plugin-required${
                                        'active' !== noorStarterParams.plugins[slug].state &&
                                        'bundle' === noorStarterParams.plugins[slug].src
                                          ? ' bundle-install-required'
                                          : ''
                                      }`}>
                                      {noorStarterParams.plugins[slug].title}{' '}
                                      <span class="plugin-status">
                                        {'notactive' === noorStarterParams.plugins[slug].state
                                          ? __('Not Installed', 'noor-starter-templates')
                                          : noorStarterParams.plugins[slug].state}
                                      </span>{' '}
                                      {'active' !== noorStarterParams.plugins[slug].state &&
                                      'thirdparty' === noorStarterParams.plugins[slug].src ? (
                                        <span class="plugin-install-required">
                                          {__(
                                            'Please install and activate this third-party premium Plugin'
                                          )}
                                        </span>
                                      ) : (
                                        ''
                                      )}
                                    </li>
                                  );
                                } else {
                                  return (
                                    <li className={`plugin-required`}>
                                      {slug}{' '}
                                      <span class="plugin-status">
                                        {__('Unknown', 'noor-starter-templates')}
                                      </span>
                                    </li>
                                  );
                                }
                              })}
                            </ul>
                          </Fragment>
                        )}
                      </Fragment>
                    )}
                  </div>
                  {this.state.colorPalette && (
                    <Fragment>
                      <h3>{__('Selected Color Palette', 'noor-starter-templates')}</h3>
                      {map(NOOR_PALETTES, ({ palette, colors }) => {
                        if (palette !== this.state.colorPalette) {
                          return;
                        }
                        return (
                          <div className="nst-palette-btn nst-selected-color-palette">
                            {map(colors, (color, index) => {
                              return (
                                <div
                                  key={index}
                                  style={{
                                    width: 22,
                                    height: 22,
                                    marginBottom: 0,
                                    marginRight: '3px',
                                    transform: 'scale(1)',
                                    transition: '100ms transform ease'
                                  }}
                                  className="noor-swatche-item-wrap">
                                  <span
                                    className={'noor-swatch-item'}
                                    style={{
                                      height: '100%',
                                      display: 'block',
                                      width: '100%',
                                      border: '1px solid rgb(218, 218, 218)',
                                      borderRadius: '50%',
                                      color: `${color}`,
                                      boxShadow: `inset 0 0 0 ${30 / 2}px`,
                                      transition: '100ms box-shadow ease'
                                    }}></span>
                                </div>
                              );
                            })}
                          </div>
                        );
                      })}
                    </Fragment>
                  )}
                  {this.state.fontPair && (
                    <Fragment>
                      <h3 className="nst-selected-font-pair-title">
                        {__('Selected Font Pair', 'noor-starter-templates')}
                      </h3>
                      {map(this.state.fonts, ({ font, img, name }) => {
                        if (font !== this.state.fontPair) {
                          return;
                        }
                        return (
                          <div className="nst-selected-font-pair">
                            <img src={img} className="font-pairing" />
                            <h4>{name}</h4>
                          </div>
                        );
                      })}
                    </Fragment>
                  )}
                </PanelBody>
                {!this.state.isFetching && (
                  <PanelBody
                    title={__('Advanced Settings', 'noor-blocks')}
                    initialOpen={this.state.settingOpen}
                    onToggle={(value) =>
                      this.state.settingOpen
                        ? this.setState({ settingOpen: false })
                        : this.setState({ settingOpen: true })
                    }>
                    <ToggleControl
                      label={__('Import Customizer Settings', 'noor-starter-templates')}
                      checked={
                        undefined !== this.state.installCustomizer
                          ? this.state.installCustomizer
                          : false
                      }
                      onChange={(value) =>
                        this.state.installCustomizer
                          ? this.setState({ installCustomizer: false })
                          : this.setState({ installCustomizer: true })
                      }
                    />
                    <ToggleControl
                      label={__('Import Content', 'noor-starter-templates')}
                      checked={
                        undefined !== this.state.installContent ? this.state.installContent : false
                      }
                      onChange={(value) =>
                        this.state.installContent
                          ? this.setState({ installContent: false })
                          : this.setState({ installContent: true })
                      }
                    />
                    <ToggleControl
                      label={__('Import Widget', 'noor-starter-templates')}
                      checked={
                        undefined !== this.state.installWidgets ? this.state.installWidgets : false
                      }
                      onChange={(value) =>
                        this.state.installWidgets
                          ? this.setState({ installWidgets: false })
                          : this.setState({ installWidgets: true })
                      }
                    />
                  </PanelBody>
                )}
                {pluginsPremium && (
                  <div className="install-third-party-notice">
                    <p className="desc-small">
                      {__(
                        'This starter template requires premium third-party plugins. Please install missing/inactive premium plugins to import.',
                        'noor-starter-templates'
                      )}
                    </p>
                    {map(this.state.templatePlugins, ({ state, src, title }) => {
                      if ('active' === state || 'repo' === src) {
                        return;
                      }
                      if ('active' !== state && 'bundle' === src) {
                        pluginsBundle = true;
                      }
                      if ('active' !== state && 'thirdparty' === src) {
                        pluginsPremium = true;
                      }
                      return (
                        <li
                          className={`plugin-required${
                            'active' !== state && 'bundle' === src ? ' bundle-install-required' : ''
                          }`}>
                          {title}{' '}
                          <span class="plugin-status">
                            {'notactive' === state
                              ? __('Not Installed', 'noor-starter-templates')
                              : state}
                          </span>
                        </li>
                      );
                    })}
                  </div>
                )}
                {this.state.progress === 'subscribe' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.subscribe_progress}
                  </div>
                )}
                {this.state.progress === 'remove' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.remove_progress}
                  </div>
                )}
                {this.state.progress === 'plugins' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.plugin_progress}
                  </div>
                )}
                {this.state.progress === 'content' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.content_progress}
                  </div>
                )}
                {this.state.progress === 'contentNew' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.content_new_progress}
                  </div>
                )}
                {this.state.progress === 'contentNewer' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.content_newer_progress}
                  </div>
                )}
                {this.state.progress === 'contentNewest' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.content_newest_progress}
                  </div>
                )}
                {this.state.progress === 'customizer' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.customizer_progress}
                  </div>
                )}
                {this.state.progress === 'widgets' && (
                  <div class="noor_starter_templates_response">
                    <Spinner />
                    {noorStarterParams.widgets_progress}
                  </div>
                )}
                {noorStarterParams.isNoor && (
                  <Fragment>
                    {pluginsPremium && (
                      <Fragment>
                        {pluginsBundle && (
                          <div class="flex flex-column align-center noor_starter_templates_response">
                            <h2>
                              {__(
                                'Install Missing/Inactive Highlighted Premium plugins to Import..',
                                'noor-starter-templates'
                              )}
                            </h2>
                            <Button
                              component="a"
                              target="_blank"
                              isPrimary
                              href={'https://link.pixeldima.com/noor-home'}>
                              {__('Get Noor Theme', 'noor-starter-templates')}
                            </Button>
                          </div>
                        )}
                        {!pluginsBundle && (
                          <Button
                            className="dima-defaults-save import-partial-btn"
                            isPrimary
                            disabled={this.state.isFetching}
                            onClick={() => {
                              if (this.state.removePast) {
                                this.runRemovePast(
                                  item._ID,
                                  this.state.starterSettings['builderType']
                                );
                              } else {
                                this.runPluginInstall(
                                  item._ID,
                                  this.state.starterSettings['builderType']
                                );
                              }
                            }}>
                            {__('Skip and Import with Partial Content')}
                          </Button>
                        )}
                      </Fragment>
                    )}
                    {!pluginsPremium && (
                      <Fragment>
                        {pluginsBundle && !noorStarterParams.isMraigal && (
                          <div class="flex flex-column align-center noor_starter_templates_response">
                            <h2>
                              {__(
                                'Install Missing/Inactive Highlighted Premium plugins to Import',
                                'noor-starter-templates'
                              )}
                            </h2>
                            <Button
                              component="a"
                              target="_blank"
                              isPrimary
                              href={'https://link.pixeldima.com/noor-home'}>
                              {__('Get Noor Theme', 'noor-starter-templates')}
                            </Button>
                          </div>
                        )}
                        {(!pluginsBundle || (pluginsBundle && noorStarterParams.isMraigal)) && (
                          <Fragment>
                            {this.state.showForm && !this.state.isSubscribed && (
                              <Fragment>
                                <NoorSubscribeForm
                                  emailError={this.state.emailError}
                                  onRun={(email) => this.runSubscribe(email, item._ID)}
                                />
                                <Button
                                  className="dima-skip-start"
                                  isPrimary
                                  disabled={this.state.isFetching}
                                  onClick={() => {
                                    if (this.state.removePast) {
                                      this.runRemovePast(
                                        item._ID,
                                        this.state.starterSettings['builderType']
                                      );
                                    } else {
                                      this.runPluginInstall(
                                        item._ID,
                                        this.state.starterSettings['builderType']
                                      );
                                    }
                                  }}>
                                  {__('Skip, Start Importing')}
                                </Button>
                              </Fragment>
                            )}
                            {this.state.showForm && this.state.isSubscribed && (
                              <Fragment>
                                <Button
                                  className="dima-defaults-save"
                                  isPrimary
                                  disabled={this.state.isFetching}
                                  onClick={() => {
                                    if (this.state.removePast) {
                                      this.runRemovePast(
                                        item._ID,
                                        this.state.starterSettings['builderType']
                                      );
                                    } else {
                                      this.runPluginInstall(
                                        item._ID,
                                        this.state.starterSettings['builderType']
                                      );
                                    }
                                  }}>
                                  {__('Start Importing', 'noor-starter-templates')}
                                </Button>
                              </Fragment>
                            )}
                          </Fragment>
                        )}
                      </Fragment>
                    )}
                  </Fragment>
                )}
              </Fragment>
            )}
          </Modal>
        </Fragment>
      );
    };

    const MainPanel = () => (
      <Fragment>
        {errorMessageShow ? ( //errorMessageShow
          <div className="main-panel">
            <div className="nst-overlay-saving">
              {!this.state.errorTemplates && (
                <Box sx={{ pt: 0.5 }}>
                  <Grid container spacing={2}>
                    <Grid item xs={12}>
                      <Skeleton variant="rectangular" height={60} animation="wave" />
                    </Grid>
                    <Grid item xs={4}>
                      <Skeleton variant="rectangular" height={450} animation="wave" />
                    </Grid>
                    <Grid item xs={4}>
                      <Skeleton variant="rectangular" height={450} animation="wave" />
                    </Grid>
                    <Grid item xs={4}>
                      <Skeleton variant="rectangular" height={450} animation="wave" />
                    </Grid>
                  </Grid>
                </Box>
              )}
              {this.state.errorTemplates && (
                <Fragment>
                  <h2 style={{ textAlign: 'center' }}>
                    {__(
                      'Error, Unable to access template database, please try re-downloading',
                      'noor-starter-templates'
                    )}
                  </h2>
                  <div style={{ textAlign: 'center' }}>
                    <Button
                      className="dima-reload-templates"
                      icon={update}
                      onClick={() => this.reloadTemplateData()}>
                      {__(' Sync with Cloud', 'noor-starter-templates')}
                    </Button>
                  </div>
                </Fragment>
              )}
              {false === this.state.activeTemplates && (
                <Fragment>{this.loadTemplateData()}</Fragment>
              )}
            </div>
          </div>
        ) : (
          <div className="main-panel">
            {this.state.focusMode && (
              <Fragment>
                {this.state.isImporting && (
                  <Fragment>
                    {!this.state.isPageSelected ? (
                      <NoorImportMode {...this.state} />
                    ) : (
                      <NoorImportSingleMode />
                    )}
                  </Fragment>
                )}
                {!this.state.isImporting && this.state.isSelected && (
                  <NoorImporterFullPreview
                    item={this.state.activeTemplates[this.state.activeTemplate]}
                    colorPalette={this.state.colorPalette}
                    fontPair={this.state.fontPair}
                    onChange={(value) => {
                      this.setState(value);
                    }}
                  />
                )}
                {!this.state.isImporting && !this.state.isSelected && <NoorSiteMode />}
              </Fragment>
            )}
            {!this.state.focusMode && !this.state.finished && <NoorSitesGrid />}
            {this.state.finished && (
              <Fragment>
                {!this.state.isPageSelected ? <NoorFinished /> : <NoorFinishedPage />}
              </Fragment>
            )}
          </div>
        )}
      </Fragment>
    );

    return (
      <Fragment>
        <div class="noor_theme_dash_head">
          <div class="noor_theme_dash_head_container">
            <div class="noor_theme_dash_logo">
              <img src={this.state.logo} />
            </div>
            {this.state.focusMode && (
              <div class="noor_theme_dash_back">
                {this.state.isPageSelected ? (
                  <Tooltip text={__('Back to Individual Pages Grid')}>
                    <Button
                      className="dima-import-back"
                      icon={chevronLeft}
                      onClick={() =>
                        this.state.isFetching
                          ? false
                          : this.setState({
                              colorPalette: '',
                              finished: false,
                              selectedPage: 'home',
                              focusMode: true,
                              isSelected: false,
                              isPageSelected: false,
                              isImporting: false,
                              progress: ''
                            })
                      }></Button>
                  </Tooltip>
                ) : (
                  <Tooltip text={__('Back to Starter Templates Grid')}>
                    <Button
                      className="dima-import-back"
                      icon={chevronLeft}
                      onClick={() => this.backToDash()}
                    />
                  </Tooltip>
                )}
              </div>
            )}
            {this.state.finished && (
              <div class="noor_theme_dash_back">
                <Tooltip text={__('Back to Starter Templates Grid')}>
                  <Button
                    className="dima-import-back"
                    icon={chevronLeft}
                    onClick={() => this.backToDash()}
                  />
                </Tooltip>
              </div>
            )}
            <div class="noor_starter_builder_type">
              {this.state.starterSettings && this.state.starterSettings['builderType'] && (
                <Dropdown
                  className="my-container-class-name"
                  contentClassName="nst-type-popover"
                  position="bottom left"
                  renderToggle={({ isOpen, onToggle }) => (
                    <Button onClick={onToggle} aria-expanded={isOpen} icon={builderTypeIcon}>
                      {builderTypeName}
                      <Icon className="nst-chev" icon={ICONS.angleDown} />
                    </Button>
                  )}
                  renderContent={({ isOpen, onToggle }) => (
                    <div>
                      <MenuItem
                        icon={ICONS.gbIcon}
                        className={
                          'blocks' === this.state.starterSettings['builderType']
                            ? 'active-item'
                            : ''
                        }
                        isSelected={
                          'blocks' === this.state.starterSettings['builderType'] ? true : false
                        }
                        onClick={() => {
                          this.saveConfig('builderType', 'blocks');
                          this.setState({
                            activeTemplate: '',
                            activePlugins: false,
                            colorPalette: '',
                            finished: false,
                            selectedPage: 'home',
                            focusMode: false,
                            isSelected: false,
                            isPageSelected: false,
                            isImporting: false,
                            progress: '',
                            activeTemplates: false
                          });
                          onToggle();
                        }}>
                        {__('Gutenberg', 'noor-starter-templates')}
                      </MenuItem>
                      <MenuItem
                        icon={ICONS.eIcon}
                        className={
                          'elementor' === this.state.starterSettings['builderType']
                            ? 'active-item'
                            : ''
                        }
                        isSelected={
                          'elementor' === this.state.starterSettings['builderType'] ? true : false
                        }
                        onClick={() => {
                          this.saveConfig('builderType', 'elementor');

                          this.setState({
                            activeTemplate: '',
                            activePlugins: false,
                            colorPalette: '',
                            finished: false,
                            selectedPage: 'home',
                            focusMode: false,
                            isSelected: false,
                            isPageSelected: false,
                            isImporting: false,
                            progress: '',
                            activeTemplates: false
                          });
                          onToggle();
                        }}>
                        {__('Elementor', 'noor-starter-templates')}
                      </MenuItem>
                      <MenuItem
                        icon={ICONS.vcIcon}
                        className={
                          'wpbakery' === this.state.starterSettings['builderType']
                            ? 'active-item'
                            : ''
                        }
                        isSelected={
                          'wpbakery' === this.state.starterSettings['builderType'] ? true : false
                        }
                        onClick={() => {
                          this.saveConfig('builderType', 'wpbakery');
                          this.setState({
                            activeTemplate: '',
                            activePlugins: false,
                            colorPalette: '',
                            finished: false,
                            selectedPage: 'home',
                            focusMode: false,
                            isSelected: false,
                            isPageSelected: false,
                            isImporting: false,
                            progress: '',
                            activeTemplates: false
                          });
                          onToggle();
                        }}>
                        {__('WPBakery', 'noor-starter-templates')}
                      </MenuItem>
                      {noorStarterParams.ctemplates && (
                        <MenuItem
                          icon={
                            noorStarterParams.custom_icon ? (
                              <img
                                className="custom-image-icon-src"
                                src={noorStarterParams.custom_icon}
                              />
                            ) : (
                              ICONS.cIcon
                            )
                          }
                          className={
                            'custom' === this.state.starterSettings['builderType']
                              ? 'active-item'
                              : ''
                          }
                          isSelected={
                            'custom' === this.state.starterSettings['builderType'] ? true : false
                          }
                          onClick={() => {
                            this.saveConfig('builderType', 'custom');
                            this.setState({
                              activeTemplate: '',
                              activePlugins: false,
                              colorPalette: '',
                              finished: false,
                              selectedPage: 'home',
                              focusMode: false,
                              isSelected: false,
                              isPageSelected: false,
                              isImporting: false,
                              progress: '',
                              activeTemplates: false
                            });
                            onToggle();
                          }}>
                          {noorStarterParams.custom_name
                            ? noorStarterParams.custom_name
                            : __('Pro Designs', 'noor-starter-templates')}
                        </MenuItem>
                      )}
                    </div>
                  )}
                />
              )}
            </div>
            {false !== this.state.activeTemplates && this.state.starterSettings['builderType'] && (
              <div class="noor_theme_dash_reload">
                <Tooltip text={__('Sync with Cloud')}>
                  <Button
                    className="dima-reload-templates"
                    icon={ICONS.sync}
                    onClick={() => this.reloadTemplateData()}
                  />
                </Tooltip>
              </div>
            )}
          </div>
        </div>
        <div class="noor_theme_starter_dash_inner">
          {localStorage.getItem('_builderType') ? (
            <MainPanel />
          ) : (
            <ChooseBuilder />
          )}
        </div>
      </Fragment>
    );
  }
}

wp.domReady(() => {
  render(<NoorImporter />, document.querySelector('.noor_starter_dashboard_main'));
});

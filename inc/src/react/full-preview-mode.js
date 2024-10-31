/**
 * Internal dependencies
 */
import map from "lodash/map";
import ICONS from "../icons";

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { Fragment, Component } = wp.element;
import { NOOR_PALETTES } from "./colors";
const { ButtonGroup, Button, ExternalLink, Tooltip } = wp.components;
import { sendPostMessage } from "../utils/functions";

class NoorImporterFullPreview extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      colorPalette: this.props.colorPalette ? this.props.colorPalette : "",
      fontPair: this.props.fontPair ? this.props.fontPair : "",
      palettes: noorStarterParams.palettes ? noorStarterParams.palettes : [],
      fonts: noorStarterParams.fonts ? noorStarterParams.fonts : [],
    };
  }
  capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }
  render() {
    const item = this.props.item;
    const isPro = !noorStarterParams.isMraigal;
    let pluginsActive = true;
    let pluginsPremium = false;
    let pluginsBundle = false;
    return (
      <div
        className="noor-starter-templates-preview theme-install-overlay wp-full-overlay expanded"
        style={{ display: "block" }}
      >
        <div className="wp-full-overlay-sidebar">
          <div className="wp-full-overlay-header">
            <button
              className="nst-close-focus-btn close-full-overlay"
              onClick={() =>
                this.props.onChange({
                  activeTemplate: "",
                  colorPalette: "",
                  fontPair: "",
                  focusMode: false,
                })
              }
            ></button>
          </div>
          <div className="wp-full-overlay-sidebar-content">
            <div className="install-theme-info">
              <div className="theme-info-wrap">
                <div className="theme-info-title-wrap">
                  <h3 className="theme-name">{item.name}</h3>
                  <div className="theme-by">
                    {item.categories
                      .map((category) => this.capitalizeFirstLetter(category))
                      .join(", ")}
                  </div>
                </div>
              </div>
              <div className="palette-title-wrap">
                <h2 className="palette-title">
                  {__(
                    "Optional: Change Color Scheme",
                    "noor-starter-templates"
                  )}
                </h2>
                <Button
                  label={__("clear")}
                  className="nst-clear-palette"
                  disabled={this.state.colorPalette ? false : true}
                  icon="image-rotate"
                  iconSize={10}
                  onClick={() => {
                    this.setState({ colorPalette: "" });
                    sendPostMessage({
                      param: "color",
                      data: "",
                    });
                    sendPostMessage({ color: "" });
                  }}
                />
              </div>
              <ButtonGroup
                className="nst-palette-group"
                aria-label={__("Select a Palette", "noor-starter-templates")}
              >
                {map(NOOR_PALETTES, ({ palette, colors }) => {
                  return (
                    <Button
                      className="nst-palette-btn"
                      isPrimary={palette === this.state.colorPalette}
                      aria-pressed={palette === this.state.colorPalette}
                      onClick={() => {
                        sendPostMessage({ color: palette });
                        this.setState({ colorPalette: palette });
                      }}
                    >
                      {map(colors, (color, index) => {
                        if (4 > index) {
                          return (
                            <div
                              key={index}
                              style={{
                                width: 30,
                                height: 30,
                                marginBottom: 0,
                                marginRight: "3px",
                                transform: "scale(1)",
                                transition: "100ms transform ease",
                              }}
                              className="noor-swatche-item-wrap"
                            >
                              <span
                                className={"noor-swatch-item"}
                                style={{
                                  height: "100%",
                                  display: "block",
                                  width: "100%",
                                  border: "1px solid rgb(218, 218, 218)",
                                  borderRadius: "50%",
                                  color: `${color}`,
                                  boxShadow: `inset 0 0 0 ${30 / 2}px`,
                                  transition: "100ms box-shadow ease",
                                }}
                              ></span>
                            </div>
                          );
                        }
                      })}
                    </Button>
                  );
                })}
              </ButtonGroup>
              <p className="desc-small">
                {__(
                  "You can change this after import.",
                  "noor-starter-templates"
                )}
              </p>
              <div className="font-title-wrap">
                <h2 className="font-title">
                  {__("Optional: Change Font Family", "noor-starter-templates")}
                </h2>
                <Button
                  label={__("clear")}
                  className="nst-clear-font"
                  disabled={this.state.fontPair ? false : true}
                  // icon="image-rotate"
                  icon={ICONS.sync}
                  iconSize={10}
                  onClick={() => {
                    this.setState({ fontPair: "" });
                    sendPostMessage({ font: "" });
                  }}
                />
              </div>
              <ButtonGroup
                className="nst-font-group"
                aria-label={__("Select a Font", "noor-starter-templates")}
              >
                {map(this.state.fonts, ({ font, img, name }) => {
                  return (
                    <Tooltip text={name}>
                      <Button
                        className={`nst-font-btn${
                          font === this.state.fontPair ? " active" : ""
                        }`}
                        aria-pressed={font === this.state.fontPair}
                        onClick={() => {
                          this.setState({ fontPair: font });
                          sendPostMessage({ font: font });
                        }}
                      >
                        <img src={img} className="font-pairing" />
                      </Button>
                    </Tooltip>
                  );
                })}
              </ButtonGroup>
              <p className="desc-small">
                {__(
                  "You can change this after import.",
                  "noor-starter-templates"
                )}
              </p>
            </div>
            <div className="noor-starter-required-plugins">
              <h2 className="nst-required-title">
                {__("Required Plugins", "noor-starter-templates")}
              </h2>
              <ul className="noor-required-wrap">
                {map(item.plugins, (slug) => {
                  if (noorStarterParams.plugins[slug]) {
                    if ("active" !== noorStarterParams.plugins[slug].state) {
                      pluginsActive = false;
                      if (
                        "thirdparty" === noorStarterParams.plugins[slug].src
                      ) {
                        pluginsPremium = true;
                      }
                      if ("bundle" === noorStarterParams.plugins[slug].src) {
                        pluginsBundle = true;
                      }
                    }
                    return (
                      <li
                        className={`plugin-required${
                          "active" !== noorStarterParams.plugins[slug].state &&
                          "bundle" === noorStarterParams.plugins[slug].src
                            ? " bundle-install-required"
                            : ""
                        }`}
                      >
                        {noorStarterParams.plugins[slug].title} {" "}
                        <span class="plugin-status">
                          {"notactive" === noorStarterParams.plugins[slug].state
                            ? __("Not Installed", "noor-starter-templates")
                            : noorStarterParams.plugins[slug].state}
                        </span>
                      </li>
                    );
                  }
                })}
              </ul>
              {!pluginsActive && (
                <Fragment>
                  {(pluginsPremium || pluginsBundle) && (
                    <p className="desc-small">
                      {__(
                        "*Install Missing/Inactive Premium plugins to import.",
                        "noor-starter-templates"
                      )}
                    </p>
                  )}
                  {!pluginsPremium && !pluginsBundle && (
                    <p className="desc-small">
                      {__(
                        "*Missing/Inactive plugins will be installed on import.",
                        "noor-starter-templates"
                      )}
                    </p>
                  )}
                </Fragment>
              )}
              {isPro && (
                <div className="notice inline notice-alt notice-warning noor-pro-notice">
                  <p>
                    <strong>Noor Theme</strong>
                  </p>
                  <p>
                    To import this starter template you need to activate your
                    license using a <strong>Purchase code</strong>.
                  </p>
                </div>
              )}
            </div>
          </div>

          <div class="wp-full-overlay-footer">
            {isPro ? (
              <div className="dima-upgrade-notice">
                <h2 className="nst-import-options-title">
                  {__("Activation required", "noor-starter-sites")}{" "}
                </h2>
                <ExternalLink
                  className="nst-upgrade button-hero button button-primary"
                  href={"https://link.pixeldima.com/noor-home"}
                >
                  {__("Get Noor", "noor-starter-sites")}
                </ExternalLink>
              </div>
            ) : (
              <Fragment>
                <h2 className="nst-import-options-title">
                  {__("Import Options", "noor-starter-templates")}
                </h2>
                <div class="noor-starter-templates-preview-actions">
                  <button
                    className="nst-import-btn button-hero button"
                    isDisabled={isPro && "true" !== noorStarterParams.pro}
                    onClick={() =>
                      this.props.onChange({
                        isSelected: false,
                        fontPair: this.state.fontPair,
                        colorPalette: this.state.colorPalette,
                      })
                    }
                  >
                    {__("Single Page", "noor-starter-templates")}
                  </button>
                  <button
                    className="nst-import-btn button-hero button button-primary"
                    isDisabled={
                      undefined !== item.pro && "true" !== noorStarterParams.pro
                    }
                    onClick={() =>
                      this.props.onChange({
                        isImporting: true,
                        fontPair: this.state.fontPair,
                        colorPalette: this.state.colorPalette,
                      })
                    }
                  >
                    {__("Full Site", "noor-starter-templates")}
                  </button>
                </div>
              </Fragment>
            )}
          </div>
        </div>

        <div class="wp-full-overlay-main">
          <iframe
          id="noor-starter-preview"
          title="Website Preview"
          src={item.url + "?cache=bust"} />
        </div>
      </div>
    );
  }
}
export default NoorImporterFullPreview;

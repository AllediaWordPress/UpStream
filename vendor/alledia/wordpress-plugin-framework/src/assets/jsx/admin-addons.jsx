/**
 * Addons tabs class.
 *
 */
class AllexAddonsTabs extends React.Component {
    /**
     * Constructor.
     *
     * @param props
     */
    constructor (props) {
        super(props);

        // State
        this.state = {
            currentTab: this.props.currentTab
        };

        // Events
        this.handleClick = this.handleClick.bind(this);
    }

    /**
     * Handles the click event on tabs.
     *
     * @param e
     */
    handleClick (e) {
        e.preventDefault();

        this.setState({currentTab: e.target.dataset.tab});
        this.props.parentTabClickHandler(e.target.dataset.tab);
    }

    /**
     * Renders the element.
     *
     * @returns {*}
     */
    render () {

        return (
            <div className="nav-tab-wrapper">
                {this.props.addonsCount > 0 ? (
                    <div>
                        <a href="#"
                           className={this.state.currentTab == 'installed' ? 'nav-tab nav-tab-active' : 'nav-tab'}
                           data-tab="installed"
                           onClick={this.handleClick}>

                            {allexContext.labels.installed}
                        </a>

                        <a href="#"
                           className={this.state.currentTab == 'missed' ? 'nav-tab nav-tab-active' : 'nav-tab'}
                           data-tab="missed"
                           onClick={this.handleClick}>

                            {allexContext.labels.browse_more}
                        </a>
                    </div>
                ) : (null)}
            </div>
        );
    }
}

/**
 * Table of addons.
 *
 * @param {Array} addons
 * @param {String} currentTab
 * @param {String} nonce
 * @param {String} pluginName
 * @param {Int} addonsCount
 * @param {Int} addonsCountTotal
 */
class AllexAddonsTable extends React.Component {
    /**
     * The constructor.
     *
     * @param props
     */
    constructor (props) {
        super(props);
    }

    /**
     * Renders the element.
     *
     * @returns {*}
     */
    render () {
        const rows = this.props.addons.map((addon, index) => {
            // Check if the current addon belongs to the current tab, based on the state.
            if (
                (addon.isInstalled && this.props.currentTab === 'installed')
                || (!addon.isInstalled && this.props.currentTab === 'missed')
            ) {
                return (<AllexAddonsRow key={'allex_addon_' + addon.slug} addon={addon}
                                        currentTab={this.props.currentTab} nonce={this.props.nonce}
                                        pluginName={this.props.pluginName}/>);
            } else {
                return null;
            }
        });

        return (
            <section id={'allex-addons-' + this.props.currentTab} className="allex-addons-container">
                {this.props.addonsCount == this.props.addonsCountTotal ? (
                    <p className="notice allex-addons-all-installed">{allexContext.labels.all_plugins_installed}</p>
                ) : (null)}

                {rows}
            </section>
        );
    }
}

/**
 * Addons row class.
 *
 * @param {Int} index
 * @param {Object} addon
 * @param {String} currentTab
 * @param {String} nonce
 * @param {String} pluginName
 */
class AllexAddonsRow extends React.Component {
    /**
     * The constructor.
     *
     * @param props
     */
    constructor (props) {
        super(props);

        // State
        this.state = {
            licenseKey: this.props.addon.licenseKey,
            licenseStatus: this.props.addon.licenseStatus,
            messages: [],
            showForm: this.props.addon.licenseKey === '',
            processing: false
        };

        // Events
        this.onChangeLicenseKey = this.onChangeLicenseKey.bind(this);
        this.localizeLicenseStatus = this.localizeLicenseStatus.bind(this);
        this.showForm = this.showForm.bind(this);
        this.hideForm = this.hideForm.bind(this);
        this.activateLicenseKey = this.activateLicenseKey.bind(this);
        this.addMessage = this.addMessage.bind(this);
        this.focusInput = this.focusInput.bind(this);
    }

    /**
     *
     * @param status
     *
     * @returns {String}
     */
    localizeLicenseStatus (status) {
        if (status !== null && status !== '' && typeof status === 'string') {
            if (allexContext.labels['license_status_' + status]) {
                return allexContext.labels['license_status_' + status];
            }

            return status;
        }

        return '';
    }

    /**
     * Show the license key form
     */
    showForm () {
        this.setState({
            showForm: true,
            messages: []
        });
    }

    /**
     * Hide the license key form
     */
    hideForm () {
        this.setState({
            showForm: false,
            messages: []
        });
    }

    /**
     * Add a message to the state.
     *
     * @param messageText
     */
    addMessage (messageText) {
        let messages = this.state.messages;

        messages.push(messageText);

        this.setState({messages: messages});
    }

    /**
     * Set focus on the input.
     */
    focusInput () {
        jQuery('article[data-slug="' + this.props.addon.slug + '"] input').focus().select();
    }

    /**
     * Make a request to the system to activate the license key.
     */
    activateLicenseKey () {
        let component = this;

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'allex_addon_license_validate',
                key: this.state.licenseKey,
                plugin_name: this.props.pluginName,
                addon_name: this.props.addon.slug,
                addon_edd_id: this.props.addon.eddId,
                nonce: this.props.nonce
            },
            beforeSend: function (jqXHR, settings) {
                component.setState({processing: true, messages: []});
            },
            success: function (response, textStatus, jqXHR) {
                component.setState({
                    processing: false,
                    licenseStatus: response.license_status
                });

                // Error.
                if (!response.success) {
                    component.focusInput();
                    component.addMessage(response.message);

                    return;
                }

                // Invalid licenses.
                if (response.license_status !== 'valid') {
                    component.focusInput();
                    component.addMessage(component.localizeLicenseStatus(response.license_status));

                    return;
                }

                // Valid licenses.
                if (response.license_status === 'valid') {
                    component.setState({showForm: false});

                    return;
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                component.setState({processing: false});

                // Error.
                component.addMessage(textStatus + '. ' + allexContext.labels.contact_support);
            }
        });
    }

    /**
     * Event triggered when a license key is added in the input field.
     *
     * @param e
     */
    onChangeLicenseKey (e) {
        this.setState({
            licenseKey: e.target.value,
            licenseStatus: 'inactive'
        });
    }

    /**
     * Renders the element.
     *
     * @returns {*}
     */
    render () {
        let messages = this.state.messages.map((message, index) => {
            let key = this.props.addon.slug + '_' + index;

            return (<li key={key}>{message}</li>);
        });

        let showForm = this.state.showForm && this.props.currentTab === 'installed';
        let showKey = this.props.currentTab === 'installed';

        let form = <div className="allex-addon-license-form">
            <div>
                <label>{allexContext.labels.license_key}</label>

                <input
                    type="text"
                    size="40"
                    maxLength="32"
                    autoComplete="false"
                    placeholder={allexContext.labels.enter_license_key}
                    value={this.state.licenseKey}
                    onChange={this.onChangeLicenseKey}
                    disabled={this.state.processing ? 'disabled' : ''}/>

                <button
                    type="button"
                    className="button activate"
                    onClick={this.activateLicenseKey}
                    disabled={this.state.processing ? 'disabled' : ''}>

                    {this.state.processing ? allexContext.labels.please_wait : allexContext.labels.activate}
                </button>

                {this.state.processing ? (
                    <div className="lds-dual-ring"></div>
                ) : (
                    <i className="dashicons dashicons-no-alt"
                       onClick={this.hideForm}></i>
                )}
            </div>

            {this.state.processing === false ? (
                <ul className="allex-addon-messages">
                    {messages}
                </ul>
            ) : (null)}
        </div>;

        return (
            <article data-slug={this.props.addon.slug}>
                <i className={this.props.addon.iconClass}></i>

                <header>
                    <h3>{this.props.addon.title}</h3>
                    <p>{this.props.addon.description}</p>
                </header>

                {showForm ? (form) : (null)}

                {this.props.currentTab === 'installed' && !showForm ? (
                    <div className="allex-addon-license">
                        <label>{allexContext.labels.license_key}</label>
                        <code>{this.state.licenseKey}</code>
                        &nbsp;
                        <a href="#"
                           onClick={this.showForm}>{allexContext.labels.change}</a>


                        <div className="allex-addon-license-status">
                            {this.localizeLicenseStatus(this.state.licenseStatus)}
                        </div>
                    </div>
                ) : (null)}
            </article>
        );
    }
}

/**
 * Addons container class.
 *
 * @param {String} pluginName
 * @param {Int} addonsCount
 * @param {Int} addonsCountTotal
 * @param {String} browseMoreUrl
 * @param {Array} addons
 * @param {String} nonce
 *
 */
class AllexAddonsContainer extends React.Component {
    /**
     * Constructor.
     *
     * @param props
     */
    constructor (props) {
        super(props);

        // State
        this.state = {
            currentTab: this.props.addonsCount > 0 ? 'installed' : 'missed'
        };

        // Events
        this.handleTabClick = this.handleTabClick.bind(this);
    }

    /**
     * Event called by the child tabs' click event.
     *
     * @param currentTab
     */
    handleTabClick (currentTab) {
        this.setState({currentTab: currentTab});
    }

    /**
     * Renders the element.
     *
     * @returns {*}
     */
    render () {
        return (
            <div id="allex-addons-table" className={this.props.pluginName}>
                <AllexAddonsTabs
                    addonsCount={this.props.addonsCount}
                    addonsCountTotal={this.props.addonsCountTotal}
                    currentTab={this.state.currentTab}
                    parentTabClickHandler={this.handleTabClick}>
                </AllexAddonsTabs>

                {this.state.currentTab == 'missed' ? (
                    <div className="allex-addons-table-header">
                        <a href={this.props.browseMoreUrl} className="button button-primary" target="_blank">
                            {allexContext.labels.get_plugins}
                        </a>
                    </div>
                ) : (null)}

                <AllexAddonsTable addons={this.props.addons} currentTab={this.state.currentTab} nonce={this.props.nonce}
                                  pluginName={this.props.pluginName} addonsCount={this.props.addonsCount}
                                  addonsCountTotal={this.props.addonsCountTotal}/>
            </div>
        );
    }
}

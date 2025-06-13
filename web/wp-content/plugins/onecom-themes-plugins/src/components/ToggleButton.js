import {useState} from '@wordpress/element';
import {usePluginContext} from "../PluginContext";
import {handlePluginDeactivation} from "../utils/handlePluginDeactivation";

const ToggleButton = ({plugin}) => {
    const {
        updatePluginState,
        setIsLoading,
        setLoadingAction,
        setLoadingPlugin,
        setToastData, activeTab, setPluginsData
    } = usePluginContext();
    const [pluginInAction, setpluginInAction] = useState({});

    const onPluginDeactivation = () => {
        handlePluginDeactivation({plugin, setPluginsData, activeTab});
    }

    const handlePluginAction = async (action, plugin) => {
        setIsLoading(true);
        const actions = {
            'activate': ocpluginVars.labels.activating,
            'deactivate': ocpluginVars.labels.deactivating,
            'install': ocpluginVars.labels.installing
        };
        setLoadingAction(actions[action]);
        setLoadingPlugin(plugin.name); // Set plugin name
        setpluginInAction((prev) => ({...prev, [plugin.slug]: true}));


        try {
            const response = await fetch(ocpluginVars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: new URLSearchParams({
                    action: `onecom_${action}_plugin`, // onecom_install_plugin, onecom_activate_plugin, onecom_deactivate_plugin
                    plugin_slug: plugin.slug,
                    plugin_name: plugin.name,
                    download_url: plugin?.downloadLink,
                    plugin_type: plugin?.pluginType,
                }),
            });

            try {
                const result = await response.json();

                if (result.success || result.status === "success" || result.type === "success") {
                    setToastData({type: "success", message: result.message || result.data?.message});

                    updatePluginState(plugin.slug, {
                        installed: action === "install" ? true : plugin.installed,
                        activated: action === "activate" ? true : action === "deactivate" ? false : plugin.activated,
                    });

                    if (action === "deactivate" && onPluginDeactivation) {
                        onPluginDeactivation(plugin);
                    }
					// reload after success to sync the menus
					setTimeout(() => {
						if (activeTab && plugin?.slug) {
							const url = new URL(window.location.href);
							url.searchParams.set('tab', activeTab);
							url.searchParams.set('plugin', plugin.slug);
							window.location.href = url.toString();
						} else {
							window.location.reload();
						}
					}, 2500);
                } else {
                    console.log("There was an issue", result);
                    setToastData({type: "alert", message: result.message || result.data?.message});
                }
            } catch (error) {
                // Redirect if the response includes a valid URL(imagify case)
                if (response.url && response.url !== window.location.href) {
                    if (plugin.slug === "imagify") {
                        setToastData({type: "success", message: response?.message});
                        console.warn("Redirecting to Imagify:", response.url);
                        window.location.href = response.url;
                    } else {
                        console.log(error);
                        setToastData({type: "alert", message: "Something went wrong. Couldn't deactivate plugin."});
                    }
                }
            }
        } catch (error) {
            console.error(`${action} failed:`, error);
            setToastData({type: "alert", message: error.message});
        } finally {
            setpluginInAction((prev) => ({...prev, [plugin.slug]: false}));
            setIsLoading(false);
            setLoadingAction('');
            setLoadingPlugin('');
        }
    };

    // Handle Rocket Plugin Special Cases
    if (plugin.slug === "wp-rocket") {
        if (plugin.is_purchased && !plugin.installed) {
            return (
                <div className="plugin-actions gv-card-content">
                    <a
                        className="gv-button gv-button-primary"
                        target="_blank"
                        href={plugin.cpLogin}
                        data-slug={plugin.slug}
                        data-name={plugin.name}
                    >
                        {ocpluginVars.labels.activate}
                    </a>
                </div>
            );
        } else if (!plugin.installed) {
            return (
                <div className="plugin-actions gv-card-content">
                    <a className="gv-button gv-button-secondary ocwp_ocp_plugins_wp_rocket_learn_more_clicked_event" target="_blank" href={plugin.guide_url}>
                        <span>{ocpluginVars.labels.learnMore}</span>
                        <gv-icon
                            src="/wp-content/plugins/onecom-themes-plugins/assets/images/open_in_new.svg"></gv-icon>
                    </a>
                </div>
            );
        } else if (plugin.installed && plugin.activated) {
            return (
                <div className="plugin-actions gv-card-content">
                    <a type="button" className="gv-button gv-button-secondary"
                       onClick={() => handlePluginAction("deactivate", plugin)}>
                        {pluginInAction[plugin.slug] ? ocpluginVars.labels.deactivating : ocpluginVars.labels.deactivate}
                    </a>
                </div>
            );
        }
    }

    return (
        <div className="plugin-actions gv-card-content">
            {plugin.installed ? (
                plugin.activated ? (
                    <button className="gv-button gv-button-secondary"
                            onClick={() => handlePluginAction("deactivate", plugin)}>
                        {pluginInAction[plugin.slug] ? ocpluginVars.labels.deactivating : ocpluginVars.labels.deactivate}
                    </button>
                ) : (
                    <button className="gv-button gv-button-primary"
                            onClick={() => handlePluginAction("activate", plugin)}>
                        {pluginInAction[plugin.slug] ? ocpluginVars.labels.activating : ocpluginVars.labels.activate}
                    </button>
                )
            ) : (
                <button className="gv-button gv-button-secondary" onClick={() => handlePluginAction("install", plugin)}>
                    {pluginInAction[plugin.slug] ? ocpluginVars.labels.installing : ocpluginVars.labels.install}
                </button>
            )}

        </div>
    );
};

export default ToggleButton;
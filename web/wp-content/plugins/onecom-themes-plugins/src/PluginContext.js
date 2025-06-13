import {createContext, useContext, useState, useEffect,useRef} from "@wordpress/element";
import usePluginsData from "./hooks/usePluginData";
import error from "eslint-plugin-react/lib/util/error";

const PluginContext = createContext();

const PluginProvider = ({children}) => {
    const {pluginsData, setPluginsData, loadingPlugins} = usePluginsData(); // fetch & store all plugins
    const [toastData, setToastData] = useState({type: "", message: ""}); // toast messages
    const [isLoading, setIsLoading] = useState(false); // loading between actions overlay depends on this
    const [activeTab, setActiveTab] = useState(() => {
		const params = new URLSearchParams(window.location.search);
		return params.get('tab') || 'all';
	});
    const [pluginList, setPluginList] = useState(pluginsData?.[activeTab] || []); // plugin list destructured based on the tabs selected
    const [loadingAction, setLoadingAction] = useState('');  // Stores 'Installing', 'Activating', etc.
    const [loadingPlugin, setLoadingPlugin] = useState(''); // stores current plugin name in action
    const tabs = [
        {key: 'all', label: ocpluginVars.labels.all, count: pluginsData.all?.length || 0 , statsClass:'ocwp_ocp_plugins_onecom_plugins_tab_visited_event'},
        {
            key: 'recommended',
            label: ocpluginVars.labels.recommendedPlugins,
            count: pluginsData.recommended?.length || 0,
            statsClass:'ocwp_ocp_plugins_recommended_tab_visited_event'
        },
        {key: 'discouraged', label: ocpluginVars.labels.discouraged, count: pluginsData.discouraged?.length || 0,
            statsClass:'ocwp_ocp_plugins_discouraged_tab_visited_event'},
    ];


    // updates plugin states e.g installed, activated, deactivated
    const updatePluginState = (slug, newData) => {
		setPluginsData(prev => ({
			...prev,
			[activeTab]: prev[activeTab].map(plugin =>
				plugin.slug === slug ? { ...plugin, ...newData } : plugin
			),
		}));

        setPluginList(prevPlugins =>
            prevPlugins.map(plugin =>
                plugin.slug === slug ? {...plugin, ...newData} : plugin
            )
        );
    };

    // sync pluginlist on click of tabs
    useEffect(() => {
        setPluginList(pluginsData?.[activeTab] || []);
    }, [activeTab, pluginsData]);

	const hasScrolledRef = useRef(false);

	useEffect(() => {
		const params = new URLSearchParams(window.location.search);
		const targetSlug = params.get('plugin');

		// Exit early if no plugin slug in URL or already scrolled
		if (!targetSlug || hasScrolledRef.current) return;

		// Wait until pluginList for the activeTab is populated
		if (!pluginList || pluginList.length === 0) return;

		// Check if the plugin with targetSlug is in the current tab
		const pluginExistsInTab = pluginList.some(plugin => plugin.slug === targetSlug);
		if (!pluginExistsInTab) return;

		// Try to scroll to the plugin element after slight delay to allow rendering
		setTimeout(() => {
			const element = document.getElementById(`plugin-${targetSlug}`);
			if (element) {
				element.scrollIntoView({ behavior: 'smooth', block: 'center' });
				hasScrolledRef.current = true;

				// Clean up the URL
				const url = new URL(window.location.href);
				url.searchParams.delete('plugin');
				window.history.replaceState({}, document.title, url.toString());
			}
		}, 1000); // Delay ensures DOM is rendered
	}, [pluginList, activeTab]);


    return (
        <PluginContext.Provider value={{
            toastData, setToastData,
            isLoading, setIsLoading,
            activeTab, setActiveTab,
            loadingPlugins,
            pluginsData, setPluginsData, tabs,
            pluginList, setPluginList,
            loadingAction, setLoadingAction,
            loadingPlugin, setLoadingPlugin,
            updatePluginState
        }}>
            {children}
        </PluginContext.Provider>
    );
};

function usePluginContext() {
    const context = useContext(PluginContext);
    if (context === undefined) {
        throw new error('Context used outside provider');
    }
    return context;
}

export {PluginProvider, usePluginContext};
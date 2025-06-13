import {StrictMode, createRoot} from '@wordpress/element';
import {PluginProvider, usePluginContext} from "./PluginContext";
import Tabs from "./components/Tabs";
import PluginsList from "./components/PluginList";
import Toast from "./components/Toast";
import "@group.one/gravity";

const App = () => {
    const {pluginsData, activeTab} = usePluginContext();
    const discouragedPluginUrl = ocpluginVars?.discouragedListUrl;


    return (
        <>
            <Toast/>

            <Tabs/>
            <PluginsList key={activeTab + pluginsData[activeTab]?.length}
                         discouragedUrl={discouragedPluginUrl}
            />
        </>
    );
};

const rootElement = document.getElementById("oc-plugins-root");
if (rootElement) {
    const root = createRoot(rootElement);
    root.render(
        <StrictMode>
            <PluginProvider>
                <App/>
            </PluginProvider>
        </StrictMode>
    );
}
import {usePluginContext} from "../PluginContext";

export const handlePluginDeactivation = ({plugin, setPluginsData, activeTab}) => {

    setPluginsData((prevData) => {
        if (activeTab !== "discouraged") {
            return prevData; // No updates if not in discouraged tab
        }
        if (!prevData.discouraged.some((p) => p.slug === plugin.slug)) {
            return prevData;
        }
        const updatedDiscouragedPlugins = prevData.discouraged.filter((p) => p.slug !== plugin.slug);

        return {
            ...prevData,
            discouraged: updatedDiscouragedPlugins.length > 0 ? updatedDiscouragedPlugins : [],
        };
    });

};
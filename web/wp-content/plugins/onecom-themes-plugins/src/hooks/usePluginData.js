import { useState, useEffect } from '@wordpress/element';

const usePluginsData = () => {
    const [pluginsData, setPluginsData] = useState({ all: ocpluginVars.plugins });
    const [loadingPlugins, setLoadingPlugins] = useState(true);

    const fetchOtherPlugins = async (type) => {
        setLoadingPlugins(true);
        try {
            const response = await fetch(ocpluginVars.ajax_url, {
                method: 'POST',
                body: new URLSearchParams({ action: 'onecom_fetch_plugins', type }),
            });

            const result = await response.json();
            if (result.success) {
                setPluginsData((prevData) => ({
                    ...prevData,
                    [type]: result.data.plugins.flat() || [],
                }));
            }
        } catch (error) {
            console.error("Error fetching plugins", error);
        }finally {
            setLoadingPlugins(false);
        }
    };

    useEffect(() => {
        Promise.allSettled([
            !pluginsData.recommended && fetchOtherPlugins("recommended"),
            !pluginsData.discouraged && fetchOtherPlugins("discouraged"),
        ]).then(() => setLoadingPlugins(false));
    }, [pluginsData]);

    return { pluginsData, setPluginsData, loadingPlugins };
};

export default usePluginsData;
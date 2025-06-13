import PluginCard from "./PluginCard";
import SkeletonBox from "./SkeletonBox";
import NoDiscouragedPlugins from "./NoDiscouragedPlugins";
import {usePluginContext} from "../PluginContext";
import DiscouragedHeader from "./DiscouragedHeader";


const PluginsList = ({discouragedUrl}) => {
	const {loadingPlugins, activeTab, pluginList, loadingAction, loadingPlugin, isLoading} = usePluginContext();

	if (!loadingPlugins && activeTab === "discouraged" && pluginList.length === 0) {
		return <NoDiscouragedPlugins listUrl={discouragedUrl}/>;
	}

	if (!loadingPlugins && pluginList.length === 0) return <p>No plugins found.</p>;

	if (loadingPlugins && ["discouraged", "recommended"].includes(activeTab)) {
		return (
			<div className="gv-grid gv-gap-lg gv-tab-grid-cols-2 gv-desk-grid-cols-2 gv-mt-md">
				<SkeletonBox parentClass='oc-plugins-box-skeleton'/>
				<SkeletonBox parentClass='oc-plugins-box-skeleton'/>
			</div>
		);
	}

	return (
		<>
			{isLoading && <div className='loading-overlay show'>
				<div className="gv-loader-container">
					<gv-loader src="/wp-content/plugins/onecom-themes-plugins/assets/images/spinner.svg"></gv-loader>
					<p>{loadingAction} {loadingPlugin}</p>
				</div>
			</div>
			}
			{!loadingPlugins && activeTab === 'discouraged' &&
				<DiscouragedHeader listURL={discouragedUrl}/>
			}
			<div className="gv-grid gv-gap-lg gv-tab-grid-cols-1 gv-desk-grid-cols-2 gv-mt-md">
				{pluginList.map(plugin => (
					<PluginCard key={plugin.slug} plugin={plugin}/>
				))}
			</div>
		</>
	);
};

export default PluginsList;
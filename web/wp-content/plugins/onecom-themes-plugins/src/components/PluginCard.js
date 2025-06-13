import ToggleButton from "./ToggleButton";

const PluginCard = ({plugin}) => {

    const isRecommendedOrDiscouraged = plugin.pluginType === 'recommended' || plugin.pluginType === 'discouraged';

    return (
        <div id={`plugin-${plugin.slug}`} className="gv-card oc-plugins-box gv-surface-bright gv-pb-lg">
            <div className="gv-card-illustration">
                <img className="gv-tile" src={plugin.thumbnail} alt={plugin.name} width="72"
                     height="72" />
            </div>
            <div key={plugin.slug} className='gv-card-content'>

                <h3 className="gv-card-title">{plugin.name}</h3>
                <p>{plugin.description ? plugin.description : plugin.shortDescription} &nbsp;&nbsp;
                    {isRecommendedOrDiscouraged && (
                        <a
                            href={`plugin-install.php?tab=plugin-information&plugin=${plugin.slug}&TB_iframe=true&width=772&height=521`}
                            className="thickbox open-plugin-details-modal gv-action"
                            title="More details"
                        >
                            {ocpluginVars.labels.moreDetails}
                        </a>
                    )}
                </p>
                {isRecommendedOrDiscouraged && (
                    <span className="oc-plugin-authors">
                        <cite dangerouslySetInnerHTML={{__html: `By ${plugin?.author}`}}/>
                    </span>
                )}
            </div>
            <ToggleButton plugin={plugin}/>

        </div>
    );
};

export default PluginCard;
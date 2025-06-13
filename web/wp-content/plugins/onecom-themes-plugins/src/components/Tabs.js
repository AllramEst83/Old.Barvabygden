import {usePluginContext} from "../PluginContext";

const Tabs = () => {
    const {activeTab, loadingPlugins, setActiveTab, tabs} = usePluginContext();
    return (
        <>
            {/*Select will appear instead of tabs for mobile screens*/}
            <div className="gv-input gv-input-select gv-tab-select">
                <select onChange={(e) => setActiveTab(e.target.value)}>
                    {tabs.map(({key, label, count}) => (
                        <option key={key}
                                value={key}
                                className={activeTab === key ? "gv-tab-active gv-tab" : "gv-tab"}>{label} <span
                            className="count">({count})</span></option>
                    ))};
                </select>
                <gv-icon src="/wp-content/plugins/onecom-themes-plugins/assets/images/expand_more.svg"></gv-icon>
            </div>

            {/*Tabs for larger screens     */}
            <div role="tablist" className="gv-tab-list">
                {tabs.map(({key, label, count, statsClass}) => (
                    <button
                        role="tab"
                        key={key}
                        onClick={() => setActiveTab(key)}
                        className={`${statsClass} ${activeTab === key ? "gv-tab-active gv-tab" : "gv-tab"}`}
                        aria-selected={activeTab === key ? "true" : "false"}
                    >
                        <span className="gv-tab-content">{label}</span>
                        {((key === 'recommended' || key === 'discouraged') && loadingPlugins) ? (
                                <span className="gv-skeleton"></span>)
                            :
                            (<span className="gv-tab-counter">{count}</span>
                            )}

                    </button>
                ))}
            </div>
        </>
    );
};

export default Tabs;
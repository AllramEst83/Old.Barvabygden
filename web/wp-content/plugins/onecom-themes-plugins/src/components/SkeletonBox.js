const SkeletonBox = ({parentClass}) => {
    return (
        <div className={`gv-card ${parentClass}`} role="status">
            <div className="gv-card-image">
                <div className="gv-skeleton gv-radius-0 gv-h-full"></div>
            </div>
            <div className="gv-card-content">
                <div className="gv-skeleton gv-card-title"></div>
                <div className="gv-skeleton"></div>
                <div className="gv-skeleton"></div>
            </div>
            <div className="gv-card-footer">
                <div className="gv-skeleton gv-mt-sm"></div>
            </div>
            <span className="gv-sr-only">Loading</span>
        </div>
    );
}
export default SkeletonBox;
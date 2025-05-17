import React from "react";

const SortDescendingIcon = ({ size = "24px", className = "", ...props }) => (
    <svg
        fill="currentColor"
        width={size}
        height={size}
        viewBox="0 0 24 24"
        id="sort-descending"
        data-name="Flat Color"
        xmlns="http://www.w3.org/2000/svg"
        className={`icon flat-color ${className}`}
        {...props}
    >
        <g id="SVGRepo_bgCarrier" strokeWidth="0" />
        <g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round" />
        <g id="SVGRepo_iconCarrier">
            <path
                id="secondary"
                d="M10.71,7.29l-4-4a1,1,0,0,0-1.42,0l-4,4A1,1,0,0,0,2.71,8.71L5,6.41V19a1,1,0,0,0,2,0V6.41l2.29,2.3a1,1,0,0,0,1.42,0A1,1,0,0,0,10.71,7.29Z"
            />
            <path
                id="primary"
                d="M20,18H10a1,1,0,0,1,0-2H20a1,1,0,0,1,0,2Zm0-5H13a1,1,0,0,1,0-2h7a1,1,0,0,1,0,2Zm0-5H15a1,1,0,0,1,0-2h5a1,1,0,0,1,0,2Z"
            />
        </g>
    </svg>
);

export default SortDescendingIcon;

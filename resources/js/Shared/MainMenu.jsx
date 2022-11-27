import React from "react";
import MainMenuItem from "@/Shared/MainMenuItem";

export default ({ className, onClick }) => {
    return (
        <div className={className} onClick={onClick}>
            <MainMenuItem text="Dashboard" link="dashboard" icon="dashboard" />
            <MainMenuItem
                text="Organizations"
                link="organizations.index"
                icon="office"
            />
            <MainMenuItem text="Contacts" link="contacts.index" icon="users" />
            <MainMenuItem text="Reports" link="reports" icon="printer" />
        </div>
    );
};

import React, { useState, useEffect } from "react";
import CreatableSelect from "react-select/creatable";
import axios from "axios";
import AddCustomerModal from "./AddCustomerModal";

const CustomerSelect = ({ setCustomerId }) => {
    const [customers, setCustomers] = useState([]);
    const [selectedCustomer, setSelectedCustomer] = useState({value:1,label:"Walking Customer"});
    const [showModal, setShowModal] = useState(false);

    // Fetch existing customers from the backend
    useEffect(() => {
      axios.get("/admin/get/customers").then((response) => {
            const customerOptions = response?.data?.map((customer) => ({
                value: customer.id,
                label: customer.name,
            }));
            setCustomers(customerOptions);
        });
    }, []);

    useEffect(() => {
        setCustomerId(selectedCustomer?.value);
    }, [selectedCustomer]);

    const handleCreateCustomer = (inputValue) => {
        axios
            .post("/admin/create/customers", { name: inputValue })
            .then((response) => {
                const newCustomer = response.data;
                const newOption = {
                    value: newCustomer.id,
                    label: newCustomer.name,
                };
                setCustomers((prev) => [newOption,...prev]);
                setSelectedCustomer(newOption);
            })
            .catch((error) => {
                console.error("Error creating customer:", error);
            });
    };

    const handleChange = (newValue) => {
        setSelectedCustomer(newValue);
    };

    const onCustomerAdded = (newOption) => {
        setCustomers((prev) => [newOption, ...prev]);
        setSelectedCustomer(newOption);
    };

    return (
        <div className="d-flex gap-2">
            <div className="flex-grow-1">
                <CreatableSelect
                    isClearable
                    options={customers}
                    onChange={handleChange}
                    onCreateOption={handleCreateCustomer}
                    value={selectedCustomer}
                    placeholder="Select or create customer"
                    styles={{
                        control: (base) => ({
                            ...base,
                            borderRadius: '8px',
                            border: '1px solid #ced4da'
                        })
                    }}
                />
            </div>
            <button 
                type="button" 
                className="btn bg-gradient-primary text-white shadow-sm"
                onClick={() => setShowModal(true)}
                title="Add New Customer"
                style={{ width: '45px', height: '38px', borderRadius: '8px' }}
            >
                <i className="fas fa-plus"></i>
            </button>

            <AddCustomerModal 
                show={showModal} 
                onHide={() => setShowModal(false)}
                onSecondaryAction={onCustomerAdded}
            />
        </div>
    );
};

export default CustomerSelect;

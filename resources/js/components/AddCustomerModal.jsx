import React, { useState } from "react";
import axios from "axios";
import toast from "react-hot-toast";

const AddCustomerModal = ({ show, onHide, onSecondaryAction }) => {
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({
        name: "",
        phone: "",
        address: ""
    });

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!formData.name) {
            toast.error("Name is required");
            return;
        }
        if (!formData.phone) {
            toast.error("Phone number is required");
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post("/admin/create/customers", formData);
            toast.success("Customer added successfully");
            onSecondaryAction({
                value: response.data.id,
                label: response.data.name
            });
            setFormData({ name: "", phone: "", address: "" });
            onHide();
        } catch (error) {
            const message = error.response?.data?.message || "Something went wrong";
            toast.error(message);
        } finally {
            setLoading(false);
        }
    };

    if (!show) return null;

    return (
        <div className="modal fade show" style={{ display: "block", backgroundColor: "rgba(0,0,0,0.5)" }} tabIndex="-1">
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content shadow-lg border-0 border-radius-15">
                    <div className="modal-header bg-gradient-primary text-white border-bottom-0">
                        <h5 className="modal-title font-weight-bold">
                            <i className="fas fa-user-plus mr-2"></i> Quick Add Customer
                        </h5>
                        <button type="button" className="close text-white" onClick={onHide}>
                            <span>&times;</span>
                        </button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body p-4">
                            <div className="form-group mb-3">
                                <label className="small font-weight-bold text-dark text-uppercase">Full Name *</label>
                                <input
                                    type="text"
                                    name="name"
                                    className="form-control form-control-lg border-0 bg-light"
                                    placeholder="Enter customer name"
                                    value={formData.name}
                                    onChange={handleChange}
                                    autoFocus
                                />
                            </div>
                            <div className="form-group mb-3">
                                <label className="small font-weight-bold text-dark text-uppercase">Phone Number *</label>
                                <input
                                    type="text"
                                    name="phone"
                                    className="form-control form-control-lg border-0 bg-light"
                                    placeholder="Enter phone number"
                                    value={formData.phone}
                                    onChange={handleChange}
                                />
                            </div>
                            <div className="form-group mb-0">
                                <label className="small font-weight-bold text-dark text-uppercase">Address</label>
                                <textarea
                                    name="address"
                                    className="form-control form-control-lg border-0 bg-light"
                                    placeholder="Enter address"
                                    rows="2"
                                    value={formData.address}
                                    onChange={handleChange}
                                ></textarea>
                            </div>
                        </div>
                        <div className="modal-footer border-top-0 p-4">
                            <button type="button" className="btn btn-light px-4 font-weight-bold" onClick={onHide} disabled={loading}>
                                Cancel
                            </button>
                            <button type="submit" className="btn bg-gradient-primary px-4 font-weight-bold text-white shadow-sm" disabled={loading}>
                                {loading ? "Saving..." : "Save Customer"}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default AddCustomerModal;

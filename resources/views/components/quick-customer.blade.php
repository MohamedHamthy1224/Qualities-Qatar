<dialog id="quickCustomerModal" class="app-dialog" aria-labelledby="quickCustomerTitle" aria-describedby="quickCustomerDescription">
    <div class="app-customer-header">
        <div>
            <h2 id="quickCustomerTitle">New customer</h2>
            <p id="quickCustomerDescription">Add a customer for quotations, invoices and payments.</p>
        </div>
        <button type="button" onclick="closeQuickCustomerModal()" class="app-notification-close" aria-label="Close new customer form">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
        </button>
    </div>
    <form id="quickCustomerForm" action="{{ route('customers.store') }}" method="POST" data-customer-list-url="{{ route('customers.index') }}" novalidate>
        @csrf
        <div class="app-customer-fields">
            <div id="quickCustomerError" class="app-customer-error-summary" role="alert" tabindex="-1" hidden></div>
            <div class="app-customer-field">
                <label for="qc_name">Customer / organization name <span aria-hidden="true">*</span></label>
                <input type="text" id="qc_name" name="name" required maxlength="255" autocomplete="organization" placeholder="e.g. Doha British Academy" aria-describedby="qc_name_error">
                <p id="qc_name_error" class="app-customer-error" hidden></p>
            </div>
            <div class="app-customer-field">
                <label for="qc_company_name">Company / group name</label>
                <input type="text" id="qc_company_name" name="company_name" maxlength="255" placeholder="e.g. DBIS Education W.L.L" aria-describedby="qc_company_name_error">
                <p id="qc_company_name_error" class="app-customer-error" hidden></p>
            </div>
            <div class="app-customer-pair">
                <div class="app-customer-field">
                    <label for="qc_phone">Phone number</label>
                    <input type="tel" id="qc_phone" name="phone" maxlength="50" autocomplete="tel" placeholder="+974 5512 3456" aria-describedby="qc_phone_error">
                    <p id="qc_phone_error" class="app-customer-error" hidden></p>
                </div>
                <div class="app-customer-field">
                    <label for="qc_email">Email address</label>
                    <input type="email" id="qc_email" name="email" maxlength="255" autocomplete="email" placeholder="name@company.qa" aria-describedby="qc_email_error">
                    <p id="qc_email_error" class="app-customer-error" hidden></p>
                </div>
            </div>
            <div class="app-customer-field">
                <label for="qc_address">Address</label>
                <input type="text" id="qc_address" name="address" maxlength="10000" autocomplete="street-address" placeholder="e.g. Al Sadd, Doha" aria-describedby="qc_address_error">
                <p id="qc_address_error" class="app-customer-error" hidden></p>
            </div>
        </div>
        <div class="app-dialog-actions">
            <button type="button" onclick="closeQuickCustomerModal()" class="app-button-secondary">Cancel</button>
            <button type="submit" id="qc_submit_btn" class="app-button-primary">Save customer</button>
        </div>
    </form>
</dialog>

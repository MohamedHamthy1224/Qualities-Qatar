import './bootstrap';
import * as documentPricing from './pricing';
import { initializeFeedback } from './feedback';
import { initializeHeader } from './header';
import { initializeQuickCustomer } from './quick-customer';
import { initializeProductionTable } from './production';
import { initializeTablePagination } from './table-pagination';
import { initializeAppearance } from './appearance';
import { initializeWorkspaceMotion } from './workspace-motion';
import { initializeProductionDonut } from './production-donut';
import { initializePaymentForm } from './payment-form';

window.documentPricing = documentPricing;
initializeAppearance();
initializeHeader();
initializeFeedback();
initializeQuickCustomer();
initializeProductionTable();
initializeTablePagination();
initializeProductionDonut();
initializePaymentForm();
initializeWorkspaceMotion();
document.addEventListener('change', event => {
    if (event.target.matches('[data-server-page-size]')) event.target.form.requestSubmit();
});

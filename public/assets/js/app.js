'use strict';

document.documentElement.classList.add('js');

(() => {
    const pickerEl = document.getElementById('employeePickerModal');
    const userPickerEl = document.getElementById('userPickerModal');
    const secuenciaPickerEl = document.getElementById('secuenciaPickerModal');
    const providerPickerEl = document.getElementById('providerPickerModal');
    const bankPickerEl = document.getElementById('bankPickerModal');
    const clientPickerEl = document.getElementById('clientPickerModal');
    const departmentPickerEl = document.getElementById('departmentPickerModal');
    const subdepartmentPickerEl = document.getElementById('subdepartmentPickerModal');
    const positionPickerEl = document.getElementById('positionPickerModal');
    const brandPickerEl = document.getElementById('brandPickerModal');
    const familyPickerEl = document.getElementById('familyPickerModal');
    const articlePickerEl = document.getElementById('articlePickerModal');
    let currentTrigger = null;
    let pickerModal = null;
    let userTrigger = null;
    let userPickerModal = null;
    let secuenciaTrigger = null;
    let secuenciaPickerModal = null;
    let providerTrigger = null;
    let providerPickerModal = null;
    let bankTrigger = null;
    let bankPickerModal = null;
    let clientTrigger = null;
    let clientPickerModal = null;
    let departmentTrigger = null;
    let departmentPickerModal = null;
    let subdepartmentTrigger = null;
    let subdepartmentPickerModal = null;
    let positionTrigger = null;
    let positionPickerModal = null;
    let brandTrigger = null;
    let brandPickerModal = null;
    let familyTrigger = null;
    let familyPickerModal = null;
    let articleTrigger = null;
    let articlePickerModal = null;
    let employeeTable = null;
    let employeeFilter = '';

    const estadoSwitch = document.getElementById('estadoSwitch');
    const estadoHidden = document.getElementById('estadoHidden');
    const estadoBadge = document.getElementById('estadoBadge');
    if (estadoSwitch && estadoHidden) {
        const syncEstado = () => {
            const activo = estadoSwitch.checked;
            estadoHidden.value = activo ? 'activo' : 'inactivo';
            if (estadoBadge) {
                estadoBadge.textContent = activo ? 'Activo' : 'Inactivo';
                estadoBadge.classList.toggle('text-bg-success', activo);
                estadoBadge.classList.toggle('text-bg-secondary', !activo);
            }
        };
        estadoSwitch.addEventListener('change', syncEstado);
        syncEstado();
    }

    const fotoInput = document.getElementById('fotoEmpleadoInput');
    const fotoPreview = document.getElementById('employeePhotoPreview');
    const fotoPlaceholder = document.getElementById('employeePhotoPlaceholder');
    if (fotoInput && fotoPreview) {
        fotoInput.addEventListener('change', () => {
            const [file] = fotoInput.files || [];
            if (!file) {
                return;
            }
            const url = URL.createObjectURL(file);
            fotoPreview.src = url;
            fotoPreview.classList.remove('d-none');
            if (fotoPlaceholder) {
                fotoPlaceholder.classList.add('d-none');
            }
        });
    }

    if (typeof bootstrap !== 'undefined' && pickerEl) {
        pickerModal = new bootstrap.Modal(pickerEl);
    }
    if (typeof bootstrap !== 'undefined' && userPickerEl) {
        userPickerModal = new bootstrap.Modal(userPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && secuenciaPickerEl) {
        secuenciaPickerModal = new bootstrap.Modal(secuenciaPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && providerPickerEl) {
        providerPickerModal = new bootstrap.Modal(providerPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && bankPickerEl) {
        bankPickerModal = new bootstrap.Modal(bankPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && clientPickerEl) {
        clientPickerModal = new bootstrap.Modal(clientPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && departmentPickerEl) {
        departmentPickerModal = new bootstrap.Modal(departmentPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && subdepartmentPickerEl) {
        subdepartmentPickerModal = new bootstrap.Modal(subdepartmentPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && positionPickerEl) {
        positionPickerModal = new bootstrap.Modal(positionPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && brandPickerEl) {
        brandPickerModal = new bootstrap.Modal(brandPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && familyPickerEl) {
        familyPickerModal = new bootstrap.Modal(familyPickerEl);
    }
    if (typeof bootstrap !== 'undefined' && articlePickerEl) {
        articlePickerModal = new bootstrap.Modal(articlePickerEl);
    }

    document.addEventListener('click', (event) => {
        const openBtn = event.target.closest('.js-open-employee-picker');
        if (openBtn && pickerModal) {
            currentTrigger = openBtn;
            employeeFilter = (openBtn.getAttribute('data-employee-filter') || '').trim().toLowerCase();
            pickerModal.show();
            return;
        }

        const openUserBtn = event.target.closest('.js-open-user-picker');
        if (openUserBtn && userPickerModal) {
            userTrigger = openUserBtn;
            userPickerModal.show();
            return;
        }

        const openSecuenciaBtn = event.target.closest('.js-open-secuencia-picker');
        if (openSecuenciaBtn && secuenciaPickerModal) {
            secuenciaTrigger = openSecuenciaBtn;
            secuenciaPickerModal.show();
            return;
        }

        const openProviderBtn = event.target.closest('.js-open-provider-picker');
        if (openProviderBtn && providerPickerModal) {
            providerTrigger = openProviderBtn;
            providerPickerModal.show();
            return;
        }

        const openBankBtn = event.target.closest('.js-open-bank-picker');
        if (openBankBtn && bankPickerModal) {
            bankTrigger = openBankBtn;
            bankPickerModal.show();
            return;
        }

        const openClientBtn = event.target.closest('.js-open-client-picker');
        if (openClientBtn && clientPickerModal) {
            clientTrigger = openClientBtn;
            clientPickerModal.show();
            return;
        }

        const openDepartmentBtn = event.target.closest('.js-open-department-picker');
        if (openDepartmentBtn && departmentPickerModal) {
            departmentTrigger = openDepartmentBtn;
            departmentPickerModal.show();
            return;
        }

        const openSubdepartmentBtn = event.target.closest('.js-open-subdepartment-picker');
        if (openSubdepartmentBtn && subdepartmentPickerModal) {
            subdepartmentTrigger = openSubdepartmentBtn;
            subdepartmentPickerModal.show();
            return;
        }

        const openPositionBtn = event.target.closest('.js-open-position-picker');
        if (openPositionBtn && positionPickerModal) {
            positionTrigger = openPositionBtn;
            positionPickerModal.show();
            return;
        }

        const openBrandBtn = event.target.closest('.js-open-brand-picker');
        if (openBrandBtn && brandPickerModal) {
            brandTrigger = openBrandBtn;
            brandPickerModal.show();
            return;
        }

        const openFamilyBtn = event.target.closest('.js-open-family-picker');
        if (openFamilyBtn && familyPickerModal) {
            familyTrigger = openFamilyBtn;
            familyPickerModal.show();
            return;
        }

        const openArticleBtn = event.target.closest('.js-open-article-picker');
        if (openArticleBtn && articlePickerModal) {
            articleTrigger = openArticleBtn;
            articlePickerModal.show();
            return;
        }

        const row = event.target.closest('.js-employee-row');
        if (!row || !currentTrigger || !pickerModal) {
            return;
        }

        const employeeId = row.getAttribute('data-employee-id') || '';
        const targetSelector = currentTrigger.getAttribute('data-employee-target');
        const redirectPattern = currentTrigger.getAttribute('data-employee-redirect');

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = employeeId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        pickerModal.hide();

        if (redirectPattern && employeeId !== '') {
            window.location.href = redirectPattern.replace('{id}', employeeId);
        }

        return;
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-user-row');
        if (!row || !userTrigger || !userPickerModal) {
            return;
        }

        const userId = row.getAttribute('data-user-id') || '';
        const username = row.getAttribute('data-username') || '';
        const targetSelector = userTrigger.getAttribute('data-user-target');
        const usernameTarget = userTrigger.getAttribute('data-username-target');
        const redirectPattern = userTrigger.getAttribute('data-user-redirect');

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = userId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        if (usernameTarget) {
            const input = document.querySelector(usernameTarget);
            if (input) {
                input.value = username;
            }
        }

        userPickerModal.hide();

        if (redirectPattern && userId !== '') {
            window.location.href = redirectPattern.replace('{id}', userId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-secuencia-row');
        if (!row || !secuenciaTrigger || !secuenciaPickerModal) {
            return;
        }

        const secuenciaId = row.getAttribute('data-secuencia-id') || '';
        const redirectPattern = secuenciaTrigger.getAttribute('data-secuencia-redirect');

        secuenciaPickerModal.hide();

        if (redirectPattern && secuenciaId !== '') {
            window.location.href = redirectPattern.replace('{id}', secuenciaId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-provider-row');
        if (!row || !providerPickerModal) {
            return;
        }

        const providerId = row.getAttribute('data-provider-id') || '';
        const redirectPattern = providerTrigger ? providerTrigger.getAttribute('data-provider-redirect') : '';

        providerPickerModal.hide();

        if (redirectPattern && providerId !== '') {
            window.location.href = redirectPattern.replace('{id}', providerId);
            return;
        }

        if (providerId !== '') {
            window.location.href = '/mantenimientos/terceros/proveedores?id=' + encodeURIComponent(providerId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-bank-row');
        if (!row || !bankPickerModal) {
            return;
        }

        const bankId = row.getAttribute('data-bank-id') || '';
        const redirectPattern = bankTrigger ? bankTrigger.getAttribute('data-bank-redirect') : '';

        bankPickerModal.hide();

        if (redirectPattern && bankId !== '') {
            window.location.href = redirectPattern.replace('{id}', bankId);
            return;
        }

        if (bankId !== '') {
            window.location.href = '/mantenimientos/terceros/bancos?id=' + encodeURIComponent(bankId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-client-row');
        if (!row || !clientPickerModal) {
            return;
        }

        const clientId = row.getAttribute('data-client-id') || '';
        const targetSelector = clientTrigger ? clientTrigger.getAttribute('data-client-target') : '';
        const redirectPattern = clientTrigger ? clientTrigger.getAttribute('data-client-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = clientId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        clientPickerModal.hide();

        if (redirectPattern && clientId !== '') {
            window.location.href = redirectPattern.replace('{id}', clientId);
            return;
        }

        if (!targetSelector && clientId !== '') {
            window.location.href = '/mantenimientos/terceros/clientes?id=' + encodeURIComponent(clientId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-department-row');
        if (!row || !departmentPickerModal) {
            return;
        }

        const departmentId = row.getAttribute('data-department-id') || '';
        const targetSelector = departmentTrigger ? departmentTrigger.getAttribute('data-department-target') : '';
        const redirectPattern = departmentTrigger ? departmentTrigger.getAttribute('data-department-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = departmentId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        departmentPickerModal.hide();

        if (redirectPattern && departmentId !== '') {
            window.location.href = redirectPattern.replace('{id}', departmentId);
            return;
        }

        if (!targetSelector && departmentId !== '') {
            window.location.href = '/sistema/puestos?tab=departamentos&departamento_id=' + encodeURIComponent(departmentId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-subdepartment-row');
        if (!row || !subdepartmentPickerModal) {
            return;
        }

        const subdepartmentId = row.getAttribute('data-subdepartment-id') || '';
        const targetSelector = subdepartmentTrigger ? subdepartmentTrigger.getAttribute('data-subdepartment-target') : '';
        const redirectPattern = subdepartmentTrigger ? subdepartmentTrigger.getAttribute('data-subdepartment-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = subdepartmentId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        subdepartmentPickerModal.hide();

        if (redirectPattern && subdepartmentId !== '') {
            window.location.href = redirectPattern.replace('{id}', subdepartmentId);
            return;
        }

        if (!targetSelector && subdepartmentId !== '') {
            window.location.href = '/sistema/puestos?tab=subdepartamentos&subdepartamento_id=' + encodeURIComponent(subdepartmentId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-position-row');
        if (!row || !positionPickerModal) {
            return;
        }

        const positionId = row.getAttribute('data-position-id') || '';
        const targetSelector = positionTrigger ? positionTrigger.getAttribute('data-position-target') : '';
        const redirectPattern = positionTrigger ? positionTrigger.getAttribute('data-position-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = positionId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        positionPickerModal.hide();

        if (redirectPattern && positionId !== '') {
            window.location.href = redirectPattern.replace('{id}', positionId);
            return;
        }

        if (!targetSelector && positionId !== '') {
            window.location.href = '/sistema/puestos?tab=puestos&puesto_id=' + encodeURIComponent(positionId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-brand-row');
        if (!row || !brandPickerModal) {
            return;
        }

        const brandId = row.getAttribute('data-brand-id') || '';
        const targetSelector = brandTrigger ? brandTrigger.getAttribute('data-brand-target') : '';
        const redirectPattern = brandTrigger ? brandTrigger.getAttribute('data-brand-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = brandId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        brandPickerModal.hide();

        if (redirectPattern && brandId !== '') {
            window.location.href = redirectPattern.replace('{id}', brandId);
            return;
        }

        if (!targetSelector && brandId !== '') {
            window.location.href = '/mantenimientos/organizacion/marcas?id=' + encodeURIComponent(brandId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-family-row');
        if (!row || !familyPickerModal) {
            return;
        }

        const familyId = row.getAttribute('data-family-id') || '';
        const targetSelector = familyTrigger ? familyTrigger.getAttribute('data-family-target') : '';
        const redirectPattern = familyTrigger ? familyTrigger.getAttribute('data-family-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = familyId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        familyPickerModal.hide();

        if (redirectPattern && familyId !== '') {
            window.location.href = redirectPattern.replace('{id}', familyId);
            return;
        }

        if (!targetSelector && familyId !== '') {
            window.location.href = '/mantenimientos/organizacion/familias?id=' + encodeURIComponent(familyId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-article-row');
        if (!row || !articlePickerModal) {
            return;
        }

        const articleId = row.getAttribute('data-article-id') || '';
        const hasVariants = row.getAttribute('data-has-variants') === '1';
        const articleTableEl = document.getElementById('articlePickerTable');
        if (hasVariants && articleTableEl && window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable) {
            const table = window.jQuery(articleTableEl);
            if (window.jQuery.fn.dataTable.isDataTable(table)) {
                const dt = table.DataTable();
                const dtRow = dt.row(row);
                if (dtRow.child.isShown()) {
                    dtRow.child.hide();
                    row.classList.remove('shown');
                    const icon = row.querySelector('.js-variant-toggle-icon');
                    if (icon) {
                        icon.classList.remove('bi-caret-down-fill');
                        icon.classList.add('bi-caret-right-fill');
                    }
                } else {
                    const variantesMap = window.ARTICULO_VARIANTES || {};
                    const variantes = variantesMap[articleId] || [];
                    if (variantes.length === 0) {
                        return;
                    }
                    const rowsHtml = variantes.map((v) => {
                        const label = `${v.articulo_codigo || ''} - ${v.articulo_descripcion || ''}`.trim();
                        const pres = v.presentacion_descripcion || '';
                        const emp = v.empaque_descripcion || '';
                        const combo = [pres, emp].filter(Boolean).join(' / ');
                        const stock = Number(v.stock_actual || 0).toFixed(2);
                        return `<tr class="js-article-variant-row" data-article-id="${v.articulo_id}" data-presentacion-id="${v.presentacion_id}" data-empaque-id="${v.empaque_id}" style="cursor:pointer;">
                            <td>${label}</td>
                            <td>${combo}</td>
                            <td>${stock} u</td>
                        </tr>`;
                    }).join('');
                    const html = `<div class="px-2 py-2">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Articulo</th>
                                    <th>Presentacion / Empaque</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>`;
                    dtRow.child(html).show();
                    row.classList.add('shown');
                    const icon = row.querySelector('.js-variant-toggle-icon');
                    if (icon) {
                        icon.classList.remove('bi-caret-right-fill');
                        icon.classList.add('bi-caret-down-fill');
                    }
                }
                return;
            }
        }
        const targetSelector = articleTrigger ? articleTrigger.getAttribute('data-article-target') : '';
        const redirectPattern = articleTrigger ? articleTrigger.getAttribute('data-article-redirect') : '';

        if (targetSelector) {
            const input = document.querySelector(targetSelector);
            if (input) {
                input.value = articleId;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        articlePickerModal.hide();

        if (redirectPattern && articleId !== '') {
            window.location.href = redirectPattern.replace('{id}', articleId);
            return;
        }

        if (!targetSelector && articleId !== '') {
            window.location.href = '/mantenimientos/organizacion/articulos?id=' + encodeURIComponent(articleId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-article-variant-row');
        if (!row || !articlePickerModal) {
            return;
        }
        const articleId = row.getAttribute('data-article-id') || '';
        articlePickerModal.hide();

        if (articleId !== '') {
            window.location.href = '/mantenimientos/organizacion/articulos?id=' + encodeURIComponent(articleId);
        }
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-ncf-row');
        if (!row) {
            return;
        }

        const ncfId = row.getAttribute('data-ncf-id') || '';
        if (ncfId === '') {
            return;
        }

        window.location.href = '/sistema/ncf?id=' + encodeURIComponent(ncfId);
    });

    document.addEventListener('click', (event) => {
        const row = event.target.closest('.js-proveedor-row');
        if (!row) {
            return;
        }

        const proveedorId = row.getAttribute('data-proveedor-id') || '';
        if (proveedorId === '') {
            return;
        }

        window.location.href = '/mantenimientos/terceros/proveedores?id=' + encodeURIComponent(proveedorId);
    });

    document.addEventListener('click', (event) => {
        const toggleBtn = event.target.closest('.js-toggle-pass');
        if (!toggleBtn) {
            return;
        }

        const target = toggleBtn.getAttribute('data-target');
        if (!target) {
            return;
        }

        const input = document.querySelector(target);
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const icon = toggleBtn.querySelector('i');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (icon) {
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        }
    });

    const passMain = document.querySelector('.js-pass-main');
    const passConfirm = document.querySelector('.js-pass-confirm');
    if (passMain instanceof HTMLInputElement && passConfirm instanceof HTMLInputElement) {
        const rules = {
            len: document.querySelector('.js-pass-rule[data-rule="len"]'),
            upper: document.querySelector('.js-pass-rule[data-rule="upper"]'),
            num: document.querySelector('.js-pass-rule[data-rule="num"]'),
            special: document.querySelector('.js-pass-rule[data-rule="special"]'),
            match: document.querySelector('.js-pass-rule[data-rule="match"]'),
        };

        const setRule = (el, ok) => {
            if (!el) {
                return;
            }
            el.classList.remove('bi-circle', 'bi-check-circle-fill', 'text-muted', 'text-success');
            el.classList.add(ok ? 'bi-check-circle-fill' : 'bi-circle');
            el.classList.add(ok ? 'text-success' : 'text-muted');
        };

        const refreshRules = () => {
            const value = passMain.value || '';
            const confirm = passConfirm.value || '';
            setRule(rules.len, value.length >= 8);
            setRule(rules.upper, /[A-Z]/.test(value));
            setRule(rules.num, /[0-9]/.test(value));
            setRule(rules.special, /[^A-Za-z0-9]/.test(value));
            setRule(rules.match, value !== '' && value === confirm);
        };

        passMain.addEventListener('input', refreshRules);
        passConfirm.addEventListener('input', refreshRules);
        refreshRules();
    }

    const profilePassCurrent = document.getElementById('profilePasswordCurrent');
    const profilePassNew = document.getElementById('profilePasswordNew');
    const profilePassConfirm = document.getElementById('profilePasswordConfirm');
    const profilePassForm = document.getElementById('changePasswordForm');

    if (
        profilePassCurrent instanceof HTMLInputElement &&
        profilePassNew instanceof HTMLInputElement &&
        profilePassConfirm instanceof HTMLInputElement &&
        profilePassForm instanceof HTMLFormElement
    ) {
        const rules = {
            len: document.getElementById('profileRuleLen'),
            upper: document.getElementById('profileRuleUpper'),
            num: document.getElementById('profileRuleNum'),
            special: document.getElementById('profileRuleSpecial'),
            match: document.getElementById('profileRuleMatch'),
        };

        const setRule = (el, ok) => {
            if (!(el instanceof HTMLElement)) {
                return;
            }
            el.classList.remove('bi-circle', 'bi-check-circle-fill', 'text-muted', 'text-success');
            el.classList.add(ok ? 'bi-check-circle-fill' : 'bi-circle');
            el.classList.add(ok ? 'text-success' : 'text-muted');
        };

        const getState = () => {
            const value = profilePassNew.value || '';
            const confirm = profilePassConfirm.value || '';
            return {
                len: value.length >= 8,
                upper: /[A-Z]/.test(value),
                num: /[0-9]/.test(value),
                special: /[^A-Za-z0-9]/.test(value),
                match: value !== '' && value === confirm,
            };
        };

        const refreshProfileRules = () => {
            const state = getState();
            setRule(rules.len, state.len);
            setRule(rules.upper, state.upper);
            setRule(rules.num, state.num);
            setRule(rules.special, state.special);
            setRule(rules.match, state.match);
            return state;
        };

        profilePassNew.addEventListener('input', refreshProfileRules);
        profilePassConfirm.addEventListener('input', refreshProfileRules);
        refreshProfileRules();

        profilePassForm.addEventListener('submit', (event) => {
            const state = refreshProfileRules();
            const valid = state.len && state.upper && state.num && state.special && state.match;
            if (!valid) {
                event.preventDefault();
                profilePassNew.focus();
            }
        });
    }

    document.addEventListener('click', (event) => {
        const copyBtn = event.target.closest('.js-copy-text');
        if (!copyBtn) {
            return;
        }

        const text = copyBtn.getAttribute('data-copy-text') || '';
        if (text === '') {
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).catch(() => {});
        }
    });

    if (window.__openTwoFactorModal && typeof bootstrap !== 'undefined') {
        const modalEl = document.getElementById('twoFactorModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        const initGenericTables = () => {
            const tables = window.jQuery('table.table');
            if (tables.length === 0) {
                return;
            }

            tables.each(function init() {
                const tableEl = this;
                const id = tableEl.getAttribute('id') || '';
                if (id === 'employeePickerTable' || id === 'userPickerTable' || id === 'secuenciaPickerTable' || id === 'providerPickerTable' || id === 'bankPickerTable' || id === 'clientPickerTable' || id === 'localidadPickerTable' || id === 'departmentPickerTable' || id === 'subdepartmentPickerTable' || id === 'positionPickerTable' || id === 'brandPickerTable' || id === 'familyPickerTable' || id === 'articlePickerTable' || id === 'insumosTable' || id === 'produccionItemsTable' || id === 'produccionesModalTable' || id === 'produccionPickerTable' || id === 'temporalesTable' || id === 'fabricacionItemsTable' || id === 'fabricacionInsumosTable' || id === 'fabricacionPickerTable' || id === 'pedidoDetalleTable' || id === 'pedidoProductoTable') {
                    return;
                }
                if (window.jQuery.fn.dataTable.isDataTable(tableEl)) {
                    return;
                }
                const pageLengthAttr = parseInt(tableEl.getAttribute('data-page-length') || '', 10);
                const pageLength = Number.isFinite(pageLengthAttr) && pageLengthAttr > 0 ? pageLengthAttr : 10;

                window.jQuery(tableEl).DataTable({
                    pageLength,
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                        infoFiltered: '(filtrado de _MAX_)',
                    },
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        "t" +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            });
        };

        initGenericTables();

        document.addEventListener('shown.bs.tab', () => {
            if (!(window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable)) {
                return;
            }
            window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        const initProduccionPickerTable = () => {
            const table = window.jQuery('#produccionPickerTable');
            if (table.length === 0) {
                return;
            }
            if (window.jQuery.fn.dataTable.isDataTable(table)) {
                table.DataTable().columns.adjust().draw(false);
                return;
            }
            table.DataTable({
                pageLength: 8,
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                    infoFiltered: '(filtrado de _MAX_)',
                },
                dom:
                    "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                    "t" +
                    "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
            });
        };

        initProduccionPickerTable();

        const initFabricacionPickerTable = () => {
            const table = window.jQuery('#fabricacionPickerTable');
            if (table.length === 0) {
                return;
            }
            if (window.jQuery.fn.dataTable.isDataTable(table)) {
                table.DataTable().columns.adjust().draw(false);
                return;
            }
            table.DataTable({
                pageLength: 8,
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: { next: 'Sig', previous: 'Ant' },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                    infoFiltered: '(filtrado de _MAX_)',
                },
                dom:
                    "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                    "t" +
                    "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
            });
        };

        initFabricacionPickerTable();

        document.addEventListener('shown.bs.modal', (event) => {
            const modal = event.target;
            if (modal && modal.id === 'produccionPickerModal') {
                initProduccionPickerTable();
            }
            if (modal && modal.id === 'fabricacionPickerModal') {
                initFabricacionPickerTable();
            }
        });

        const initEmployeeTable = () => {
            const applyEmployeeFilter = () => {
                if (!employeeTable) {
                    return;
                }

                if (employeeFilter === 'activo') {
                    employeeTable.column(4).search('activo', false, false).draw();
                } else {
                    employeeTable.column(4).search('', false, false).draw();
                }
            };

            if (employeeTable) {
                employeeTable.columns.adjust().draw(false);
                applyEmployeeFilter();
                return;
            }

            const table = window.jQuery('#employeePickerTable');
            if (table.length === 0) {
                return;
            }

            employeeTable = table.DataTable({
                pageLength: 12,
                order: [[0, 'desc']],
                autoWidth: false,
                deferRender: true,
                language: {
                    search: '',
                    searchPlaceholder: 'Buscar por ID, cedula, nombre...',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ - _END_ de _TOTAL_',
                    paginate: {
                        first: 'Primero',
                        last: 'Ultimo',
                        next: 'Sig',
                        previous: 'Ant',
                    },
                    zeroRecords: 'Sin resultados',
                    infoEmpty: 'No hay datos',
                    infoFiltered: '(filtrado de _MAX_)',
                },
                columns: [
                    { width: '84px' },
                    { width: '170px' },
                    { width: '300px' },
                    { width: '220px' },
                    { width: '130px' },
                ],
                columnDefs: [
                    { targets: '_all', className: 'dt-nowrap' },
                ],
                dom:
                    "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                    "t" +
                    "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
            });

            table.on('draw.dt', () => {
                table.find('tbody tr').addClass('js-employee-row');
            });

            applyEmployeeFilter();
        };

        if (pickerEl) {
            pickerEl.addEventListener('shown.bs.modal', initEmployeeTable);
        }

        if (userPickerEl) {
            let userTable = null;
            const initUserTable = () => {
                if (userTable) {
                    userTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#userPickerTable');
                if (table.length === 0) {
                    return;
                }

                userTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    columns: [
                        { width: '90px' },
                        { width: '280px' },
                        { width: '240px' },
                    ],
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar usuario...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        "t" +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            userPickerEl.addEventListener('shown.bs.modal', initUserTable);
        }

        if (secuenciaPickerEl) {
            let secuenciaTable = null;
            const initSecuenciaTable = () => {
                if (secuenciaTable) {
                    secuenciaTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#secuenciaPickerTable');
                if (table.length === 0) {
                    return;
                }

                secuenciaTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar secuencia...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        "t" +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            secuenciaPickerEl.addEventListener('shown.bs.modal', initSecuenciaTable);
        }

        if (providerPickerEl) {
            let providerTable = null;
            const initProviderTable = () => {
                if (providerTable) {
                    providerTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#providerPickerTable');
                if (table.length === 0) {
                    return;
                }

                providerTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar proveedor...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            providerPickerEl.addEventListener('shown.bs.modal', initProviderTable);
        }

        if (bankPickerEl) {
            let bankTable = null;
            const initBankTable = () => {
                if (bankTable) {
                    bankTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#bankPickerTable');
                if (table.length === 0) {
                    return;
                }

                bankTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar banco...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            bankPickerEl.addEventListener('shown.bs.modal', initBankTable);
        }

        if (clientPickerEl) {
            let clientTable = null;
            const initClientTable = () => {
                if (clientTable) {
                    clientTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#clientPickerTable');
                if (table.length === 0) {
                    return;
                }

                clientTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar cliente...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            clientPickerEl.addEventListener('shown.bs.modal', initClientTable);
        }

        if (departmentPickerEl) {
            let departmentTable = null;
            const initDepartmentTable = () => {
                if (departmentTable) {
                    departmentTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#departmentPickerTable');
                if (table.length === 0) {
                    return;
                }

                departmentTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar departamento...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            departmentPickerEl.addEventListener('shown.bs.modal', initDepartmentTable);
        }

        if (subdepartmentPickerEl) {
            let subdepartmentTable = null;
            const initSubdepartmentTable = () => {
                if (subdepartmentTable) {
                    subdepartmentTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#subdepartmentPickerTable');
                if (table.length === 0) {
                    return;
                }

                subdepartmentTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar subdepartamento...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            subdepartmentPickerEl.addEventListener('shown.bs.modal', initSubdepartmentTable);
        }

        if (positionPickerEl) {
            let positionTable = null;
            const initPositionTable = () => {
                if (positionTable) {
                    positionTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#positionPickerTable');
                if (table.length === 0) {
                    return;
                }

                positionTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar puesto...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            positionPickerEl.addEventListener('shown.bs.modal', initPositionTable);
        }

        if (brandPickerEl) {
            let brandTable = null;
            const initBrandTable = () => {
                if (brandTable) {
                    brandTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#brandPickerTable');
                if (table.length === 0) {
                    return;
                }

                brandTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar marca...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            brandPickerEl.addEventListener('shown.bs.modal', initBrandTable);
        }

        if (familyPickerEl) {
            let familyTable = null;
            const initFamilyTable = () => {
                if (familyTable) {
                    familyTable.columns.adjust().draw(false);
                    return;
                }

                const table = window.jQuery('#familyPickerTable');
                if (table.length === 0) {
                    return;
                }

                familyTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar familia...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
            };

            familyPickerEl.addEventListener('shown.bs.modal', initFamilyTable);
        }

        if (articlePickerEl) {
            let articleTable = null;
            const articleTipoFilterEl = document.getElementById('articlePickerTipoFilter');
            const applyArticleTipoFilter = () => {
                if (!articleTable || !articleTipoFilterEl) {
                    return;
                }
                const tipo = (articleTipoFilterEl.value || '').trim();
                if (tipo === '') {
                    articleTable.column(2).search('').draw();
                    return;
                }
                const escapedTipo = window.jQuery.fn.dataTable.util.escapeRegex(tipo);
                articleTable.column(2).search('^' + escapedTipo + '$', true, false).draw();
            };
            const initArticleTable = () => {
                if (articleTable) {
                    articleTable.columns.adjust().draw(false);
                    applyArticleTipoFilter();
                    return;
                }

                const table = window.jQuery('#articlePickerTable');
                if (table.length === 0) {
                    return;
                }

                articleTable = table.DataTable({
                    pageLength: 10,
                    order: [[0, 'desc']],
                    autoWidth: false,
                    deferRender: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Buscar articulo...',
                        lengthMenu: 'Mostrar _MENU_',
                        info: '_START_ - _END_ de _TOTAL_',
                        paginate: { next: 'Sig', previous: 'Ant' },
                        zeroRecords: 'Sin resultados',
                        infoEmpty: 'No hay datos',
                    },
                    columnDefs: [{ targets: '_all', className: 'dt-nowrap' }],
                    dom:
                        "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'f>>" +
                        't' +
                        "<'row g-2 align-items-center mt-2'<'col-12 col-md-6'i><'col-12 col-md-6 text-md-end'p>>",
                });
                applyArticleTipoFilter();
            };

            if (articleTipoFilterEl) {
                articleTipoFilterEl.addEventListener('change', applyArticleTipoFilter);
            }
            articlePickerEl.addEventListener('shown.bs.modal', initArticleTable);
        }
    }

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const wireToastBehavior = (toastEl, delayMs) => {
        if (!toastEl || !(typeof bootstrap !== 'undefined' && bootstrap.Toast)) {
            return null;
        }
        const delay = Number.isFinite(delayMs) && delayMs > 0 ? delayMs : 5000;
        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { autohide: false });
        let timerId = null;
        let startedAt = 0;
        let remaining = delay;
        let paused = false;

        const clearHideTimer = () => {
            if (timerId) {
                clearTimeout(timerId);
                timerId = null;
            }
        };

        const scheduleHide = () => {
            clearHideTimer();
            startedAt = Date.now();
            toastEl.style.setProperty('--toast-play-state', 'running');
            timerId = setTimeout(() => {
                toast.hide();
            }, remaining);
        };

        const pauseToast = () => {
            if (paused) return;
            paused = true;
            const elapsed = Date.now() - startedAt;
            remaining = Math.max(0, remaining - elapsed);
            clearHideTimer();
            toastEl.style.setProperty('--toast-play-state', 'paused');
        };

        const resumeToast = () => {
            if (!paused) return;
            paused = false;
            if (remaining <= 0) {
                toast.hide();
                return;
            }
            scheduleHide();
        };

        toastEl.style.setProperty('--toast-delay', `${delay}ms`);
        toastEl.style.setProperty('--toast-play-state', 'running');
        toastEl.addEventListener('mouseenter', pauseToast);
        toastEl.addEventListener('mouseleave', resumeToast);
        toastEl.addEventListener('focusin', pauseToast);
        toastEl.addEventListener('focusout', resumeToast);
        toastEl.addEventListener('hidden.bs.toast', clearHideTimer, { once: true });

        toast.show();
        scheduleHide();
        return toast;
    };

    window.AppToast = window.AppToast || {};
    window.AppToast.show = ({ message = '', type = 'info', title = 'Notificacion', delay = 5000 } = {}) => {
        const text = String(message || '').trim();
        if (!text) return;
        const safeType = ['success', 'danger', 'warning', 'info'].includes(String(type)) ? String(type) : 'info';
        const container = document.createElement('div');
        container.className = 'toast-overlay-container position-fixed top-0 end-0 p-3';
        container.innerHTML = `
            <div class="toast show app-toast app-toast-${safeType} border-0 shadow-sm" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="${Number(delay) || 5000}">
                <div class="toast-header">
                    <strong class="me-auto">${escapeHtml(title || 'Notificacion')}</strong>
                    <button type="button" class="btn-close ms-2 mb-1" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
                <div class="toast-body">${escapeHtml(text)}</div>
            </div>
        `;
        document.body.appendChild(container);
        const toastEl = container.querySelector('.toast');
        const wired = wireToastBehavior(toastEl, Number(delay) || 5000);
        if (!wired) {
            setTimeout(() => container.remove(), (Number(delay) || 5000) + 200);
            return;
        }
        toastEl.addEventListener('hidden.bs.toast', () => container.remove(), { once: true });
    };
    const pendingToasts = Array.isArray(window.__pendingAppToasts) ? window.__pendingAppToasts : [];
    if (pendingToasts.length > 0) {
        pendingToasts.forEach((item) => {
            if (!item || typeof item !== 'object') return;
            window.AppToast.show({
                message: item.message || '',
                type: item.type || 'info',
                title: item.title || 'Notificacion',
                delay: Number(item.delay) || 5000,
            });
        });
        window.__pendingAppToasts = [];
    }

    const globalToastEl = document.getElementById('globalToast');
    if (globalToastEl) {
        const rawDelay = parseInt(globalToastEl.getAttribute('data-bs-delay') || '5000', 10);
        wireToastBehavior(globalToastEl, rawDelay);
    }

    const loadingOverlay = document.getElementById('globalLoadingOverlay');
    if (loadingOverlay) {
        document.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') {
                return;
            }

            loadingOverlay.classList.add('is-visible');
            loadingOverlay.setAttribute('aria-hidden', 'false');

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
                btn.setAttribute('disabled', 'disabled');
            });
        });
    }
})();

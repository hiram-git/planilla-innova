/**
 * Módulo JavaScript para selectores jerárquicos de departamentos
 * Gestiona la cascada de selectores basándose en la estructura padre-hijo del organigrama
 *
 * @version 1.0.0
 * @date 2025-12-19
 */

(function() {
    'use strict';

    /**
     * Clase para gestionar selectores jerárquicos de departamentos
     */
    class DepartamentosJerarquicos {
        constructor(containerId, options = {}) {
            this.container = document.getElementById(containerId);
            if (!this.container) {
                console.error(`Container #${containerId} not found`);
                return;
            }

            this.options = {
                apiBaseUrl: options.apiBaseUrl || window.APP_CONFIG?.urls?.organizationalApi || '/panel/organizational',
                onSelectionChange: options.onSelectionChange || null,
                selectedDepartamentoId: options.selectedDepartamentoId || null,
                fieldName: options.fieldName || 'departamento_id',
                showLabels: options.showLabels !== undefined ? options.showLabels : true,
                requiredField: options.requiredField !== undefined ? options.requiredField : true,
                labelText: options.labelText || 'Departamento',
                emptyText: options.emptyText || 'Seleccionar...',
                ...options
            };

            this.selects = []; // Array de selectores creados
            this.currentPath = []; // Ruta actual seleccionada (array de IDs)
            this.selectedValue = null; // Valor final seleccionado

            this.init();
        }

        /**
         * Inicializar el módulo
         */
        async init() {
            console.log('[DepartamentosJerarquicos] Initializing...');

            // Limpiar contenedor
            this.container.innerHTML = '';

            if (this.options.selectedDepartamentoId) {
                // Modo edición: reconstruir ruta jerárquica
                await this.loadHierarchyPath(this.options.selectedDepartamentoId);
            } else {
                // Modo creación: mostrar solo selector raíz
                await this.addSelectLevel(null, 0);
            }

            console.log('[DepartamentosJerarquicos] Initialized successfully');
        }

        /**
         * Cargar y reconstruir ruta jerárquica para modo edición
         */
        async loadHierarchyPath(departamentoId) {
            try {
                const response = await fetch(`${this.options.apiBaseUrl}/getHierarchyPath/${departamentoId}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error loading hierarchy path');
                }

                // data.path contiene [raíz, ...padres, departamento_actual]
                const path = data.path || [];

                // Crear selectores para cada nivel de la jerarquía
                for (let i = 0; i < path.length; i++) {
                    const element = path[i];
                    const parentId = i === 0 ? null : path[i - 1].id;

                    await this.addSelectLevel(parentId, i, element.id);
                }

                this.selectedValue = departamentoId;

                // ✅ Actualizar campo oculto con el valor inicial
                this.updateHiddenField();

                this.triggerChangeEvent();

            } catch (error) {
                console.error('[DepartamentosJerarquicos] Error loading hierarchy path:', error);
                toastr.error('Error al cargar la jerarquía del departamento', 'Error');
                // Fallback: mostrar solo selector raíz
                await this.addSelectLevel(null, 0);
            }
        }

        /**
         * Agregar un nuevo nivel de selector
         */
        async addSelectLevel(parentId, level, selectedValue = null) {
            try {
                // Crear contenedor del selector
                const selectWrapper = document.createElement('div');
                selectWrapper.className = 'form-group dept-select-level';
                selectWrapper.dataset.level = level;

                // Label opcional
                if (this.options.showLabels) {
                    const label = document.createElement('label');
                    label.textContent = level === 0
                        ? this.options.labelText
                        : `${this.options.labelText} - Nivel ${level + 1}`;
                    if (this.options.requiredField && level === 0) {
                        label.innerHTML += ' <span class="text-danger">*</span>';
                    }
                    selectWrapper.appendChild(label);
                }

                // Crear select
                const select = document.createElement('select');
                select.className = 'form-control dept-hierarchical-select';
                select.dataset.level = level;
                select.dataset.parentId = parentId || 'root';

                // ✅ NO asignar name a los selectores jerárquicos
                // El valor se enviará mediante el campo <input type="hidden" name="edit_departamento_id">
                // Esto evita conflictos cuando hay múltiples niveles

                // NO aplicar required a selectores jerárquicos
                // El campo oculto ya tiene la validación necesaria

                // Agregar opción vacía
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = this.options.emptyText;
                select.appendChild(emptyOption);

                // Mensaje de loading
                const loadingOption = document.createElement('option');
                loadingOption.value = '';
                loadingOption.textContent = 'Cargando...';
                loadingOption.disabled = true;
                select.appendChild(loadingOption);

                selectWrapper.appendChild(select);

                // Agregar pequeño texto de ayuda
                const helpText = document.createElement('small');
                helpText.className = 'form-text text-muted';
                helpText.innerHTML = level === 0
                    ? '<i class="fas fa-sitemap"></i> Seleccione el nivel más alto de la estructura'
                    : '<i class="fas fa-level-down-alt"></i> Nivel jerárquico descendiente';
                selectWrapper.appendChild(helpText);

                // Agregar al contenedor
                this.container.appendChild(selectWrapper);

                // Cargar opciones
                await this.loadSelectOptions(select, parentId, selectedValue);

                // Event listener para cambios
                select.addEventListener('change', (e) => this.handleSelectChange(e, level));

                // Guardar referencia
                this.selects[level] = select;

            } catch (error) {
                console.error('[DepartamentosJerarquicos] Error adding select level:', error);
                toastr.error('Error al crear selector de departamento', 'Error');
            }
        }

        /**
         * Cargar opciones de un selector desde la API
         */
        async loadSelectOptions(selectElement, parentId, selectedValue = null) {
            try {
                const endpoint = parentId === null || parentId === 'root'
                    ? `${this.options.apiBaseUrl}/getChildrenByParent/root`
                    : `${this.options.apiBaseUrl}/getChildrenByParent/${parentId}`;

                const response = await fetch(endpoint);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Error loading departments');
                }

                // Limpiar opciones de loading
                selectElement.innerHTML = '';

                // Opción vacía
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = this.options.emptyText;
                selectElement.appendChild(emptyOption);

                // Agregar departamentos
                const children = data.children || [];
                children.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.descripcion;
                    option.dataset.hasChildren = dept.children_count > 0 ? 'true' : 'false';
                    option.dataset.childrenCount = dept.children_count || 0;

                    // Seleccionar si coincide
                    if (selectedValue && dept.id == selectedValue) {
                        option.selected = true;
                    }

                    selectElement.appendChild(option);
                });

                // Si no hay hijos, mostrar mensaje
                if (children.length === 0) {
                    const noChildOption = document.createElement('option');
                    noChildOption.value = '';
                    noChildOption.textContent = 'Sin subdepartamentos';
                    noChildOption.disabled = true;
                    selectElement.appendChild(noChildOption);
                }

            } catch (error) {
                console.error('[DepartamentosJerarquicos] Error loading select options:', error);
                selectElement.innerHTML = '<option value="">Error al cargar</option>';
            }
        }

        /**
         * Manejar cambio en un selector
         */
        async handleSelectChange(event, level) {
            const select = event.target;
            const selectedOption = select.options[select.selectedIndex];
            const departamentoId = select.value;

            console.log(`[DepartamentosJerarquicos] Level ${level} changed to:`, departamentoId);

            // Eliminar selectores de niveles inferiores
            this.removeSelectsAfterLevel(level);

            if (!departamentoId) {
                // Valor vacío seleccionado
                this.updateCurrentPath(level, null);
                this.updateFinalValue();
                return;
            }

            // Actualizar ruta actual
            this.updateCurrentPath(level, departamentoId);

            // Actualizar el atributo name del select actual
            this.updateSelectNames(level);

            // ✅ Disparar evento INMEDIATAMENTE cuando se selecciona un departamento
            // Esto permite cargar los cargos asociados a ESTE nivel específico
            this.selectedValue = departamentoId;
            this.updateHiddenField();
            console.log(`[DepartamentosJerarquicos] Updated hidden field for level ${level}, value: ${this.selectedValue}`);
            this.triggerChangeEvent();

            // Verificar si tiene hijos
            const hasChildren = selectedOption.dataset.hasChildren === 'true';
            const childrenCount = parseInt(selectedOption.dataset.childrenCount || 0);

            if (hasChildren && childrenCount > 0) {
                // Agregar siguiente nivel de selección
                await this.addSelectLevel(departamentoId, level + 1);
            }
        }

        /**
         * Actualizar ruta actual (path de IDs seleccionados)
         */
        updateCurrentPath(level, departamentoId) {
            // Truncar path hasta el nivel especificado
            this.currentPath = this.currentPath.slice(0, level);

            // Agregar nuevo valor si no es null
            if (departamentoId) {
                this.currentPath.push(departamentoId);
            }

            console.log('[DepartamentosJerarquicos] Current path:', this.currentPath);
        }

        /**
         * Actualizar atributo name de los selectores
         * ✅ FIX: NO asignar name a los selects dinámicos, solo actualizar campo oculto
         * Esto evita conflictos cuando hay múltiples niveles jerárquicos
         */
        updateSelectNames(currentLevel) {
            // Remover name de todos los selects dinámicos
            this.selects.forEach((select, index) => {
                if (select) {
                    select.removeAttribute('name');
                }
            });

            // ✅ NO asignar name a selectores jerárquicos - solo usar campo oculto
            // El valor se enviará mediante el campo <input type="hidden" name="edit_departamento_id">

            // Actualizar campo oculto con el valor seleccionado actual
            this.updateHiddenField();
            console.log(`[DepartamentosJerarquicos] Updated hidden field for level ${currentLevel}, value: ${this.selectedValue}`);
        }

        /**
         * Actualizar campo oculto con el valor seleccionado actual
         * Esto garantiza que el valor se envíe en el formulario aunque los selects dinámicos cambien
         */
        updateHiddenField() {
            // Buscar campo oculto con el mismo nombre
            const hiddenField = document.querySelector(`input[type="hidden"][name="${this.options.fieldName}"]`);

            if (hiddenField) {
                hiddenField.value = this.selectedValue || '';
                console.log(`[DepartamentosJerarquicos] Updated hidden field value:`, this.selectedValue);
            }
        }

        /**
         * Actualizar valor final
         * ✅ FIX: Solo actualizar campo oculto, no asignar name a selects
         */
        updateFinalValue() {
            // El valor final es el último elemento del path
            this.selectedValue = this.currentPath.length > 0
                ? this.currentPath[this.currentPath.length - 1]
                : null;

            // Remover name de todos los selects dinámicos
            this.selects.forEach(select => {
                if (select) {
                    select.removeAttribute('name');
                }
            });

            // ✅ NO asignar name a selectores jerárquicos - solo usar campo oculto
            // El valor se enviará mediante el campo <input type="hidden">

            console.log('[DepartamentosJerarquicos] Final value:', this.selectedValue);

            // ✅ Actualizar campo oculto con el valor final
            this.updateHiddenField();

            this.triggerChangeEvent();
        }

        /**
         * Eliminar selectores después de un nivel específico
         */
        removeSelectsAfterLevel(level) {
            // Eliminar del DOM
            const selectsToRemove = this.container.querySelectorAll(`.dept-select-level[data-level]`);
            selectsToRemove.forEach(wrapper => {
                const wrapperLevel = parseInt(wrapper.dataset.level);
                if (wrapperLevel > level) {
                    wrapper.remove();
                }
            });

            // Limpiar array de selectores
            this.selects = this.selects.slice(0, level + 1);
        }

        /**
         * Disparar evento de cambio personalizado
         */
        triggerChangeEvent() {
            if (typeof this.options.onSelectionChange === 'function') {
                this.options.onSelectionChange({
                    value: this.selectedValue,
                    path: this.currentPath,
                    level: this.currentPath.length
                });
            }

            // Disparar evento DOM nativo
            const event = new CustomEvent('departamentoJerarquicoChange', {
                detail: {
                    value: this.selectedValue,
                    path: this.currentPath,
                    level: this.currentPath.length
                },
                bubbles: true
            });
            this.container.dispatchEvent(event);
        }

        /**
         * Obtener valor seleccionado actual
         */
        getValue() {
            return this.selectedValue;
        }

        /**
         * Obtener ruta completa seleccionada
         */
        getPath() {
            return this.currentPath;
        }

        /**
         * Resetear selectores
         */
        reset() {
            this.currentPath = [];
            this.selectedValue = null;
            this.selects = [];
            this.init();
        }

        /**
         * Establecer valor programáticamente
         */
        async setValue(departamentoId) {
            if (!departamentoId) {
                this.reset();
                return;
            }

            this.options.selectedDepartamentoId = departamentoId;
            await this.loadHierarchyPath(departamentoId);
        }
    }

    // Exportar a window para uso global
    window.DepartamentosJerarquicos = DepartamentosJerarquicos;

    console.log('[DepartamentosJerarquicos] Module loaded successfully');

})();

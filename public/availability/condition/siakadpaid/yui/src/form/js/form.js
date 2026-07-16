/**
 * JavaScript for editing SIAKAD paid availability conditions.
 *
 * @module moodle-availability_siakadpaid-form
 */
M.availability_siakadpaid = M.availability_siakadpaid || {};

/**
 * @class M.availability_siakadpaid.form
 * @extends M.core_availability.plugin
 */
M.availability_siakadpaid.form = Y.Object(M.core_availability.plugin);

/**
 * Available study programs.
 *
 * @property prodis
 * @type Array
 */
M.availability_siakadpaid.form.prodis = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} prodis Array of objects with code and name.
 */
M.availability_siakadpaid.form.initInner = function(prodis) {
    this.prodis = prodis || [];
};

M.availability_siakadpaid.form.getNode = function(json) {
    var html = '<label><span class="pe-3">' + M.util.get_string('title', 'availability_siakadpaid') + '</span> ' +
            '<span class="availability-group">' +
            '<select name="prodi" class="form-select">' +
            '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>' +
            '<option value="*">' + M.util.get_string('anyprodi', 'availability_siakadpaid') + '</option>';

    for (var i = 0; i < this.prodis.length; i++) {
        var prodi = this.prodis[i];
        html += '<option value="' + prodi.code + '">' + prodi.name + '</option>';
    }

    html += '</select></span></label>';

    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');
    var select = node.one('select[name=prodi]');

    if (json.creating === undefined && json.prodi !== undefined) {
        var option = select.one('option[value="' + json.prodi + '"]');
        if (option) {
            select.set('value', json.prodi);
        }
    }

    if (!M.availability_siakadpaid.form.addedEvents) {
        M.availability_siakadpaid.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_siakadpaid select');
    }

    return node;
};

M.availability_siakadpaid.form.fillValue = function(value, node) {
    value.prodi = node.one('select[name=prodi]').get('value');
};

M.availability_siakadpaid.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    if (!value.prodi || value.prodi === 'choose') {
        errors.push('availability_siakadpaid:error_selectprodi');
    }
};

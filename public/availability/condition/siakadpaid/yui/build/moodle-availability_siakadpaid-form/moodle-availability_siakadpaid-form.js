YUI.add('moodle-availability_siakadpaid-form', function (Y, NAME) {
/**
 * Editing form for the SIAKAD billing availability condition.
 *
 * @module moodle-availability_siakadpaid-form
 */
M.availability_siakadpaid = M.availability_siakadpaid || {};
M.availability_siakadpaid.form = Y.Object(M.core_availability.plugin);
M.availability_siakadpaid.form.config = {prodis: [], defaults: {}};
M.availability_siakadpaid.form.initInner = function(config) { this.config = config || {prodis: [], defaults: {}}; };
M.availability_siakadpaid.form.getNode = function(json) {
    var config = this.config || {prodis: [], defaults: {}};
    var defaults = config.defaults || {};
    var html = '<div class="availability-group d-flex flex-wrap gap-2 align-items-center">' +
        '<label>' + M.util.get_string('label_prodi', 'availability_siakadpaid') + ' <select name="prodi" class="form-select d-inline-block w-auto">' +
        '<option value="choose">' + M.util.get_string('choose', 'availability_siakadpaid') + '</option>' +
        '<option value="*">' + M.util.get_string('anyprodi', 'availability_siakadpaid') + '</option>';
    for (var i = 0; i < (config.prodis || []).length; i++) {
        html += '<option value="' + Y.Escape.html(config.prodis[i].code) + '">' + Y.Escape.html(config.prodis[i].name) + '</option>';
    }
    html += '</select></label>' +
        '<label>' + M.util.get_string('label_tahunajaran', 'availability_siakadpaid') + ' <input class="form-control d-inline-block w-auto" type="text" name="tahunajaran"></label>' +
        '<label>' + M.util.get_string('label_semester', 'availability_siakadpaid') + ' <select class="form-select d-inline-block w-auto" name="semester">' +
        '<option value="">-</option><option value="ganjil">' + M.util.get_string('semester_ganjil', 'availability_siakadpaid') + '</option>' +
        '<option value="genap">' + M.util.get_string('semester_genap', 'availability_siakadpaid') + '</option></select></label>' +
        '<label>' + M.util.get_string('label_jenis', 'availability_siakadpaid') + ' <input class="form-control d-inline-block w-auto" type="text" name="jenis" placeholder="' +
        M.util.get_string('allbilltypes', 'availability_siakadpaid') + '"></label></div>';
    var node = Y.Node.create('<span class="availability_siakadpaid">' + html + '</span>');
    node.one('[name=prodi]').set('value', json.prodi || 'choose');
    node.one('[name=tahunajaran]').set('value', json.tahunajaran !== undefined ? json.tahunajaran : (defaults.tahunajaran || ''));
    node.one('[name=semester]').set('value', json.semester !== undefined ? json.semester : (defaults.semester || ''));
    node.one('[name=jenis]').set('value', json.jenis || '');
    node.all('select,input').on('change', function() { M.core_availability.form.update(); });
    return node;
};
M.availability_siakadpaid.form.fillValue = function(value, node) {
    value.prodi = node.one('[name=prodi]').get('value');
    value.tahunajaran = node.one('[name=tahunajaran]').get('value').trim();
    value.semester = node.one('[name=semester]').get('value');
    value.jenis = node.one('[name=jenis]').get('value').trim();
};
M.availability_siakadpaid.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);
    if (!value.prodi || value.prodi === 'choose') { errors.push('availability_siakadpaid:error_selectprodi'); }
};
}, '@VERSION@', {"requires": ["base", "node", "event", "escape", "moodle-core_availability-form"]});

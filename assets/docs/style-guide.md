# UFSC Admin Style Guide

## Design Tokens

```css
:root {
  --ufsc-color-primary: #2e2d54;
  --ufsc-color-accent: #d40000;
  --ufsc-color-success: #46b450;
  --ufsc-color-warning: #ffb900;
  --ufsc-color-bg-light: #f5f5f5;
  --ufsc-color-border: #e0e0e0;
  --ufsc-color-text: #333333;
  --ufsc-font-size-base: clamp(1rem, 0.5vw + 1rem, 1.125rem);
  --ufsc-spacing-base: 1rem;
}
```

## Components

### Buttons

```html
<a class="ufsc-btn">Primary Action</a>
<a class="ufsc-btn ufsc-btn-red">Danger Action</a>
<a class="ufsc-btn ufsc-btn-outline">Secondary Action</a>
```

### Alerts

```html
<div class="ufsc-alert ufsc-alert-success">Succès</div>
<div class="ufsc-alert ufsc-alert-error">Erreur</div>
<div class="ufsc-alert ufsc-alert-warning">Alerte</div>
```

### Card

```html
<div class="ufsc-card">
  <div class="ufsc-card-header">Titre</div>
  <div class="ufsc-card-body">Contenu de la carte...</div>
</div>
```

## Sample Markup

### Table

```html
<table class="wp-list-table ufsc-table">
  <thead>
    <tr>
      <th>Colonne</th>
      <th>Valeur</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Nom</td>
      <td>UFSC</td>
    </tr>
  </tbody>
</table>
```

### Form

```html
<form class="ufsc-form">
  <label for="club-name">Nom du club <span class="ufsc-form-required">*</span></label>
  <input id="club-name" type="text" />

  <label for="club-type">Type</label>
  <select id="club-type">
    <option>Association</option>
    <option>Entreprise</option>
  </select>

  <button type="submit" class="ufsc-btn">Valider</button>
</form>
```


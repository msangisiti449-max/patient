<?php
/**
 * Public-site text shared by the landing page and the Services page.
 * Edit here to change the wording everywhere.
 */

const SERVICES = [
    'records' => [
        'icon' => 'fa-notes-medical', 'color' => 'blue', 'title' => 'Medical records',
        'summary' => 'See your medical history and the diagnoses your doctors have recorded.',
        'details' => 'Every visit adds to your record. You can read each diagnosis, the date it was made, the doctor who made it and their clinical notes, so you always know your health history and can share it accurately with other providers.',
        'points' => ['Full history of diagnoses', 'Doctor notes for each visit', 'Your personal details in one place', 'Updated as soon as your doctor saves'],
    ],
    'appointments' => [
        'icon' => 'fa-calendar-check', 'color' => 'teal', 'title' => 'Book appointments',
        'summary' => 'Schedule hospital visits online with the doctor of your choice.',
        'details' => 'Pick a doctor, a date up to 90 days ahead and a free 30-minute slot between 08:00 and 17:00, Monday to Saturday. Your request goes straight to the doctor, and you are notified when it is confirmed. You can cancel online if your plans change.',
        'points' => ['Choose doctor, date and time', 'No double-booking of slots', 'Confirmation notification', 'Cancel online any time before'],
    ],
    'lab-results' => [
        'icon' => 'fa-flask', 'color' => 'amber', 'title' => 'Laboratory results',
        'summary' => 'Check blood tests and other examination results as soon as they are ready.',
        'details' => 'Each result shows the test name, your value, the normal range, a clear status (normal, abnormal or critical) and remarks from your doctor. You get a notification whenever a new result is added.',
        'points' => ['Result with normal range', 'Colour-coded status', 'Doctor remarks', 'Alert when a new result arrives'],
    ],
    'prescriptions' => [
        'icon' => 'fa-prescription-bottle-medical', 'color' => 'red', 'title' => 'Prescription renewals',
        'summary' => 'View your medicines and request renewals where supported.',
        'details' => 'See every prescription with its dosage, frequency and dates. For regular medicines your doctor marks as renewable, send a renewal request with one click and an optional note. The doctor approves or declines and you are notified either way.',
        'points' => ['Dosage and frequency', 'One-click renewal request', 'Doctor approval and note', 'Daily medication reminders'],
    ],
    'messages' => [
        'icon' => 'fa-comments', 'color' => 'blue', 'title' => 'Message your doctor',
        'summary' => 'Send secure messages to your healthcare providers.',
        'details' => 'Ask a question about your results, treatment or next visit. Messages are private between you and the doctor, with an inbox, sent folder and replies. For emergencies, always call the hospital instead.',
        'points' => ['Private inbox and replies', 'Notification on new messages', 'Written record of advice', 'Not for emergencies'],
    ],
    'bills' => [
        'icon' => 'fa-file-invoice-dollar', 'color' => 'teal', 'title' => 'Bills & payments',
        'summary' => 'Check your medical bills, balances and payment history.',
        'details' => 'Every invoice shows what it is for, the amount, what you have paid and the remaining balance. Payments made at the cashier, by mobile money, card, bank or insurance are recorded and appear in your history.',
        'points' => ['Itemised invoices', 'Balance and due dates', 'Full payment history', 'Reminder before a bill is due'],
    ],
    'reminders' => [
        'icon' => 'fa-bell', 'color' => 'amber', 'title' => 'Reminders',
        'summary' => 'Receive appointment, medication and bill reminders automatically.',
        'details' => 'The portal reminds you of appointments in the next 24 hours, your daily medication times and bills due within three days. Reminders appear on your dashboard and in the notification bell.',
        'points' => ['Appointment reminders', 'Daily medication times you set', 'Bill due-date alerts', 'Notification bell on every page'],
    ],
    'security' => [
        'icon' => 'fa-shield-halved', 'color' => 'red', 'title' => 'Secure by design',
        'summary' => 'Your data is protected with encrypted passwords and secure sessions.',
        'details' => 'Passwords are stored encrypted, accounts lock after repeated wrong attempts, sessions end automatically after 30 minutes of inactivity, and every access to records is logged. Only you and your care team can see your information.',
        'points' => ['Encrypted passwords', 'Automatic sign-out', 'Account lockout protection', 'Access audit log'],
    ],
];

const FAQS = [
    ['Who can use the patient portal?', 'Any patient of the hospital can create a free account using the Register page. Doctors and staff accounts are created by the hospital administrator.'],
    ['How do I book an appointment?', 'Sign in, open Appointments and click Book appointment. Choose a doctor, date and time and describe the reason for your visit. You will be notified when the doctor confirms it.'],
    ['Can I cancel or change an appointment?', 'Yes. Open Appointments and click Cancel on any upcoming visit, then book a new time if you need to.'],
    ['When will my lab results appear?', 'As soon as your doctor enters them. You receive a notification, and the result shows its normal range and status. Ask your doctor through Messages if anything is unclear.'],
    ['How do I renew a prescription?', 'Open Prescriptions and click Request renewal on any medicine marked as renewable. Your doctor reviews it and you are notified of the decision.'],
    ['Can I use messages for an emergency?', 'No. Messages are not monitored around the clock. In an emergency call ' . HOSPITAL_EMERGENCY . ' or go to the nearest emergency department.'],
    ['How do I pay my bills?', 'Pay at the hospital cashier, by mobile money, card, bank transfer or insurance. Once the payment is recorded, your bill status and payment history update in the portal.'],
    ['Is my medical information safe?', 'Yes. Passwords are encrypted, sessions expire automatically, accounts lock after repeated failed sign-ins, and only you and your care team can view your records. See Privacy & Security for details.'],
    ['I forgot my password. What do I do?', 'Use Forgot password on the sign-in page. The administrator is notified and will give you a temporary password after confirming your identity at reception.'],
    ['Does the portal cost anything?', 'No. The patient portal is free for all patients of the hospital.'],
];

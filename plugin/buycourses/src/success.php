<?php
/* For license terms, see /license.txt */
/**
 * Success page for the purchase of a course in the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';

$plugin = BuyCoursesPlugin::create();

$sale = $plugin->getSale($_SESSION['bc_sale_id']);

if (empty($sale)) {
    api_not_allowed(true);
}

$buyingCourse = false;
$buyingSession = false;

switch ($sale['product_type']) {
    case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
        $buyingCourse = true;
        $course = $plugin->getCourseInfo($sale['product_id']);
        break;
    case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
        $buyingSession = true;
        $session = $plugin->getSessionInfo($sale['product_id']);
        break;
}

$paypalParams = $plugin->getPaypalParams();

$pruebas = $paypalParams['sandbox'] == 1;
$paypalUsername = $paypalParams['username'];
$paypalPassword = $paypalParams['password'];
$paypalSignature = $paypalParams['signature'];

require_once("paypalfunctions.php");

$form = new FormValidator('success', 'POST', api_get_self(), null, null, FormValidator::LAYOUT_INLINE);
$form->addButton('confirm', $plugin->get_lang('ConfirmOrder'), 'check', 'success');
$form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    if (isset($formValues['cancel'])) {
        $plugin->cancelSale($sale['id']);

        unset($_SESSION['bc_sale_id']);

        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/index.php');
        exit;
    }

    $confirmPayments = ConfirmPayment($sale['price']);

    if ($confirmPayments['ACK'] !== 'Success') {
        var_dump([
            'error_code' => $confirmPayments['L_ERRORCODE0'],
            'short_message' => $confirmPayments['L_SHORTMESSAGE0'],
            'long_message' => $confirmPayments['L_LONGMESSAGE0'],
            'severity_code' => $confirmPayments['L_SEVERITYCODE0']
        ]);
        exit;
    }

    $transactionId = $confirmPayments["PAYMENTINFO_0_TRANSACTIONID"];
    $transactionType = $confirmPayments["PAYMENTINFO_0_TRANSACTIONTYPE"];

    switch ($confirmPayments["PAYMENTINFO_0_PAYMENTSTATUS"]) {
        case 'Completed':
            $saleIsCompleted = $plugin->completeSale($sale['id']);

            if ($saleIsCompleted && $buyingCourse) {
                Display::addFlash(
                    Display::return_message(
                        sprintf($plugin->get_lang('EnrollToCourseXSuccessful'), $session['name']),
                        'success'
                    )
                );
                break;
            }

            if ($saleIsCompleted && $buyingSession) {
                Display::addFlash(
                    Display::return_message(
                        sprintf($plugin->get_lang('EnrollToSessionXSuccessful'), $course['name']),
                        'success'
                    )
                );
                break;
            }

            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
        case 'Pending':
            switch ($confirmPayments["PAYMENTINFO_0_PENDINGREASON"]) {
                case 'address':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByAddress');
                    break;
                case 'authorization':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByAuthorization');
                    break;
                case 'echeck':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByEcheck');
                    break;
                case 'intl':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByIntl');
                    break;
                case 'multicurrency':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByMulticurrency');
                    break;
                case 'order':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByOrder');
                    break;
                case 'paymentreview':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByPaymentReview');
                    break;
                case 'regulatoryreview':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByRegulatoryReview');
                    break;
                case 'unilateral':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByUnilateral');
                    break;
                case 'upgrade':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByUpgrade');
                    break;
                case 'verify':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByVerify');
                    break;
                case 'other':
                    //no break
                default:
                    $purchaseStatus = $plugin->get_lang('PendingReasonByOther');
                    break;
            }

            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('PurchaseStatusX'), $purchaseStatus),
                    'warning'
                )
            );
            break;
        default:
            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
    }

    unset($_SESSION['bc_sale_id']);
    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/list.php');
    exit;
}

$token = isset($_GET['token']) ? $_GET['token'] : null;

if (empty($token)) {
    api_not_allowed(true);
}

$shippingDetails = GetShippingDetails($token);

if ($shippingDetails['ACK'] !== 'Success') {
    var_dump([
        'error_code' => $shippingDetails['L_ERRORCODE0'],
        'short_message' => $shippingDetails['L_SHORTMESSAGE0'],
        'long_message' => $shippingDetails['L_LONGMESSAGE0'],
        'severity_code' => $shippingDetails['L_SEVERITYCODE0']
    ]);
    exit;
}

$interbreadcrumb[] = array("url" => "list.php", "name" => $plugin->get_lang('CourseListOnSale'));

$templateName = $plugin->get_lang('PaymentMethods');
$tpl = new Template($templateName);

if ($buyingCourse) {
    $tpl->assign('course', $course);
} elseif ($buyingSession) {
    $tpl->assign('session', $session);
}

$tpl->assign('buying_course', $buyingCourse);
$tpl->assign('buying_session', $buyingSession);
$tpl->assign('title', $sale['product_name']);
$tpl->assign('price', $sale['price']);
$tpl->assign('currency', $sale['currency_id']);
$tpl->assign('user', api_get_user_info($sale['user_id']));
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('buycourses/view/success.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();

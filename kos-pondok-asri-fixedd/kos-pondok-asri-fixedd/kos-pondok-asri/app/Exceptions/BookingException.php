<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis booking/pembayaran. Pesannya aman ditampilkan ke pengguna.
 */
class BookingException extends RuntimeException
{
}

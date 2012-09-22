using System;
using System.Collections.Generic;
using System.Collections.Specialized;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Net.Security;
using System.Security.Cryptography;
using System.Security.Cryptography.X509Certificates;
using System.Text;
using System.Threading.Tasks;

namespace PhpCloudBoxClientLibrary
{
	public class PhpCloudBoxUtils
	{
		static bool AllowInvalidCertificatesOnce = false;

		static public void AllowInvalidCertificates()
		{
			if (!AllowInvalidCertificatesOnce)
			{
				// allows for validation of SSL conversations
				ServicePointManager.ServerCertificateValidationCallback += (sender, certificate, chain, policyErrors) =>
				{
					return true;
				};

				AllowInvalidCertificatesOnce = true;
			}
		}

		async static public Task<string> Sha1FileAsync(Stream LocalStream)
		{
			return await Task.Run(() =>
			{
				var sha1Bytes = SHA1.Create().ComputeHash(LocalStream);
				var sha1 = BitConverter.ToString(sha1Bytes).Replace("-", "").ToLowerInvariant();
				return sha1;
			});
		}

		async static public Task<string> Sha1FileAsync(string LocalFile)
		{
			using (Stream LocalStream = File.OpenRead(LocalFile))
			{
				return await Sha1FileAsync(LocalStream);
			}
		}

		static public string ToQueryString(NameValueCollection nvc)
		{
			return string.Join("&", Array.ConvertAll(nvc.AllKeys, key => string.Format("{0}={1}", Uri.EscapeUriString(key), Uri.EscapeUriString(nvc[key]))));
		}
	}
}
